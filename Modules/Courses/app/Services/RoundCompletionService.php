<?php

namespace Modules\Courses\Services;

use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\ExamModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\StudentScoreModel;

/**
 * Single source of truth for a student's completion rate ("معدل الإنجاز") in a round,
 * shared by the student's completion page, their courses list and admin WhatsApp alerts.
 */
class RoundCompletionService
{
    /**
     * Exam slots counted toward completion, in page order. An exam assigned to several
     * lessons fills several slots, and each slot counts in the averages.
     */
    public function examSlots(int $roundId): array
    {
        $contents = RoundContetModel::where('round_id', $roundId)
            ->whereIn('type', ['basic', 'lecture'])
            ->orderBy('sort_number', 'asc')
            ->get();

        $lessonsByContent = LessonsModel::whereIn('round_content_id', $contents->pluck('id'))
            ->get()
            ->groupBy('round_content_id');

        $lessonAssignments = AssignExamModel::where('type', 'lesson')
            ->whereIn('lesson_or_round_id', $lessonsByContent->flatten()->pluck('id'))
            ->get()
            ->groupBy('lesson_or_round_id');

        $roundAssignments = AssignExamModel::where('type', 'full_round')
            ->where('lesson_or_round_id', $roundId)
            ->get();

        $exams = ExamModel::whereIn(
            'id',
            $lessonAssignments->flatten()->pluck('exam_id')->merge($roundAssignments->pluck('exam_id'))->unique()
        )->get()->keyBy('id');

        $slots = [];
        foreach ($contents as $content) {
            foreach ($lessonsByContent->get($content->id, []) as $lesson) {
                foreach ($lessonAssignments->get($lesson->id, []) as $assignment) {
                    $exam = $exams->get($assignment->exam_id);
                    if ($exam) {
                        $slots[] = $this->slot($content->type, $content->id, $lesson->id, $exam);
                    }
                }
            }
        }
        foreach ($roundAssignments as $assignment) {
            $exam = $exams->get($assignment->exam_id);
            if ($exam) {
                $slots[] = $this->slot('full_round', null, null, $exam);
            }
        }

        return $slots;
    }

    /**
     * Best attempt per exam for each student: [studentId => [examId => ['percentage' => float, 'scored_at' => mixed]]].
     */
    public function bestScores(array $studentIds, array $examIds): array
    {
        $best = [];
        if (empty($studentIds) || empty($examIds)) {
            return $best;
        }

        $examIds = array_values(array_unique($examIds));
        foreach (array_chunk(array_values(array_unique($studentIds)), 500) as $chunk) {
            // Plain rows streamed in id order keep memory flat on large rounds.
            $rows = StudentScoreModel::query()
                ->toBase()
                ->select(['id', 'student_id', 'exam_id', 'score', 'created_at'])
                ->whereIn('student_id', $chunk)
                ->whereIn('exam_id', $examIds)
                ->lazyById(2000);
            $best = $this->pickBest($rows, $best);
        }

        return $best;
    }

    /**
     * Keeps each student's highest percentage per exam, comparing numerically (scores like
     * "18/20" must beat "9/20"). On ties the earliest attempt wins, so rows must come in id order.
     */
    public function pickBest(iterable $rows, array $best = []): array
    {
        foreach ($rows as $row) {
            $row = (object) $row;
            $percentage = self::scoreToPercentage($row->score);
            $current = $best[$row->student_id][$row->exam_id] ?? null;
            if ($current === null || $percentage > $current['percentage']) {
                $best[$row->student_id][$row->exam_id] = [
                    'percentage' => $percentage,
                    'scored_at' => $row->created_at ?? null,
                ];
            }
        }

        return $best;
    }

    /**
     * Per-exam items and category/total percentages for one student, given that student's best scores.
     */
    public function breakdown(array $slots, array $studentBest): array
    {
        $items = ['basic' => [], 'lecture' => [], 'full_round' => []];

        foreach ($slots as $slot) {
            $attempt = $studentBest[$slot['exam_id']] ?? null;
            $items[$slot['type']][] = [
                'content_id' => $slot['content_id'],
                'lesson_id' => $slot['lesson_id'],
                'exam_id' => $slot['exam_id'],
                'exam_name' => $slot['exam_name'],
                'type' => $slot['type'],
                'highest_score' => $attempt ? round($attempt['percentage'], 2) . '%' : '0%',
                'created_at' => $attempt ? self::formatDate($attempt['scored_at']) : null,
                'solved' => $attempt !== null,
            ];
        }

        $percentages = [];
        $categoryAverages = [];
        foreach ($items as $type => $typeItems) {
            $percentages[$type] = self::average($typeItems);
            if (!empty($typeItems)) {
                $categoryAverages[] = $percentages[$type];
            }
        }

        return [
            'items' => $items,
            'basic_percentage' => $percentages['basic'],
            'lecture_percentage' => $percentages['lecture'],
            'full_round_percentage' => $percentages['full_round'],
            'total_percentage' => empty($categoryAverages)
                ? 0
                : round(array_sum($categoryAverages) / count($categoryAverages), 2),
        ];
    }

    public function totalPercentage(int $roundId, int $studentId): float
    {
        $slots = $this->examSlots($roundId);
        $best = $this->bestScores([$studentId], array_column($slots, 'exam_id'));

        return $this->breakdown($slots, $best[$studentId] ?? [])['total_percentage'];
    }

    /**
     * Scores are stored either as "obtained/total" or as a plain percentage.
     */
    public static function scoreToPercentage($score): float
    {
        $score = (string) $score;
        if (strpos($score, '/') !== false) {
            $parts = explode('/', $score);
            $obtained = (float) trim($parts[0]);
            $total = (float) trim($parts[1]);

            return $total > 0 ? ($obtained / $total) * 100 : 0.0;
        }

        return (float) $score;
    }

    private static function average(array $items): float
    {
        if (empty($items)) {
            return 0;
        }
        $sum = 0;
        foreach ($items as $item) {
            $sum += (float) str_replace('%', '', $item['highest_score']);
        }

        return round($sum / count($items), 2);
    }

    // Mirrors the page's original behavior: a missing date is parsed as "now".
    private static function formatDate($date): string
    {
        return $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d H:i:s')
            : \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    private function slot(string $type, $contentId, $lessonId, $exam): array
    {
        return [
            'type' => $type,
            'content_id' => $contentId,
            'lesson_id' => $lessonId,
            'exam_id' => $exam->id,
            'exam_name' => $exam->title,
        ];
    }
}
