<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\StudentMockExamScratchModel;
use Modules\Courses\Models\UserRounds;

class MockExamScratchController extends Controller
{
    /**
     * ~300KB per question scratch (text + drawing strokes JSON).
     */
    private const MAX_PAYLOAD_BYTES = 300000;

    public function save(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
            'question_id' => 'required|integer|exists:questions,id',
            'scratch_data' => 'required|array',
        ]);

        $studentId = $request->user()->id;
        $examId = (int) $data['exam_id'];
        $questionId = (int) $data['question_id'];
        $scratchData = $data['scratch_data'];

        if (strlen(json_encode($scratchData)) > self::MAX_PAYLOAD_BYTES) {
            return res_data('حجم المسودة أكبر من المسموح', 'error', 413);
        }

        $questionBelongsToExam = AdminQuestionsModel::where('id', $questionId)
            ->whereHas('exam_section', fn ($q) => $q->where('exam_id', $examId))
            ->exists();

        if (!$questionBelongsToExam) {
            return res_data('السؤال لا يتبع هذا الامتحان', 'error', 422);
        }

        if (!$this->studentHasAccessToExam($studentId, $examId)) {
            return res_data('لا يوجد اشتراك او صلاحية لهذا الطالب لهذا الامتحان', 'error', 403);
        }

        if ($this->isScratchEmpty($scratchData)) {
            StudentMockExamScratchModel::where('student_id', $studentId)
                ->where('exam_id', $examId)
                ->where('question_id', $questionId)
                ->delete();

            return res_data(null, 'success', 200);
        }

        $scratch = StudentMockExamScratchModel::updateOrCreate(
            ['student_id' => $studentId, 'exam_id' => $examId, 'question_id' => $questionId],
            ['scratch_data' => $scratchData]
        );

        return res_data($scratch, 'success', 200);
    }

    private function isScratchEmpty(array $scratchData): bool
    {
        $text = trim((string) ($scratchData['text'] ?? ''));
        $strokes = $scratchData['drawing']['strokes'] ?? [];

        return $text === '' && empty($strokes);
    }

    /**
     * Same access tiers as ExamSectionsController::get_mock_exam_sectionsWithQuestions,
     * kept self-contained here to avoid touching that controller.
     */
    private function studentHasAccessToExam(int $studentId, int $examId): bool
    {
        $exam = AdminExamModel::find($examId);
        if (!$exam) {
            return false;
        }

        if ($exam->free == '1') {
            return true;
        }

        $assignments = AssignExamModel::where('exam_id', $examId)->get();
        foreach ($assignments as $assignment) {
            $roundId = null;
            if ($assignment->type === 'full_round') {
                $roundId = $assignment->lesson_or_round_id;
            } elseif ($assignment->type === 'lesson') {
                $lesson = LessonsModel::find($assignment->lesson_or_round_id);
                if ($lesson) {
                    $roundContent = RoundContetModel::find($lesson->round_content_id);
                    if ($roundContent) {
                        $roundId = $roundContent->round_id;
                    }
                }
            }

            if ($roundId && $this->isSubscribedOrFree($studentId, $roundId)) {
                return true;
            }
        }

        $roundId = null;
        if ($exam->round_id) {
            $roundId = $exam->round_id;
        } elseif ($exam->lesson_id) {
            $lesson = LessonsModel::find($exam->lesson_id);
            if ($lesson) {
                $roundContent = RoundContetModel::find($lesson->round_content_id);
                if ($roundContent) {
                    $roundId = $roundContent->round_id;
                }
            }
        }

        return $roundId && $this->isSubscribedOrFree($studentId, $roundId);
    }

    private function isSubscribedOrFree(int $studentId, int $roundId): bool
    {
        $isSubscribed = UserRounds::where('student_id', $studentId)->where('round_id', $roundId)->exists();
        $isFree = Rounds::where('id', $roundId)->where('free', '1')->exists();

        return $isSubscribed || $isFree;
    }
}
