<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Models\Student;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Services\RoundCompletionService;

/**
 * Completion rate (معدل الإنجاز) for the admin: pick a round, then a student. Computed exactly like
 * the student's own getMyCompeletenessRoundByRoundID (RoundCompletionService).
 */
class AdminRoundCompletionController extends Controller
{
    public function __construct(private RoundCompletionService $completion)
    {
    }

    /**
     * The round's students with their completion percentages, to pick a student from.
     * status: active (default) | all. sort: percentage_desc (default) | percentage_asc | name.
     */
    public function getRoundStudentsCompletion(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,all',
            'sort' => 'nullable|in:percentage_desc,percentage_asc,name',
            'per_page' => 'nullable|integer|min:1|max:500',
            'page' => 'nullable|integer|min:1',
        ]);

        $roundId = (int) $data['round_id'];
        $like = filled($data['search'] ?? null) ? '%' . addcslashes(trim($data['search']), '\\%_') . '%' : null;

        $enrollments = UserRounds::where('round_id', $roundId)
            ->when(($data['status'] ?? 'active') === 'active', fn ($q) => $q->where('status', 'active'))
            ->get(['student_id', 'status'])
            ->keyBy('student_id');

        $students = Student::whereIn('id', $enrollments->keys())
            ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)))
            ->get(['id', 'name', 'phone', 'gender']);

        $slots = $this->completion->examSlots($roundId);
        $examIds = array_column($slots, 'exam_id');

        $rows = [];
        foreach ($students->chunk(200) as $chunk) {
            $best = $this->completion->bestScores($chunk->pluck('id')->all(), $examIds);

            foreach ($chunk as $student) {
                $c = $this->completion->breakdown($slots, $best[$student->id] ?? []);
                $solved = count(array_filter(array_merge(...array_values($c['items'])), fn ($i) => $i['solved']));

                $rows[] = [
                    'student' => $student->only(['id', 'name', 'phone', 'gender']),
                    'enrollment_status' => $enrollments[$student->id]->status ?? null,
                    'basic_percentage' => $c['basic_percentage'] . '%',
                    'lecture_percentage' => $c['lecture_percentage'] . '%',
                    'full_round_percentage' => $c['full_round_percentage'] . '%',
                    'total_percentage' => $c['total_percentage'] . '%',
                    'total_percentage_value' => (float) $c['total_percentage'],
                    'solved_exams' => $solved,
                    'total_exams' => count($slots),
                ];
            }
        }

        $sort = $data['sort'] ?? 'percentage_desc';
        usort($rows, match ($sort) {
            'percentage_asc' => fn ($a, $b) => $a['total_percentage_value'] <=> $b['total_percentage_value'],
            'name' => fn ($a, $b) => strcmp((string) $a['student']['name'], (string) $b['student']['name']),
            default => fn ($a, $b) => $b['total_percentage_value'] <=> $a['total_percentage_value'],
        });

        $perPage = (int) ($data['per_page'] ?? 50);
        $page = (int) ($data['page'] ?? 1);
        $total = count($rows);

        return res_data([
            'round' => ['id' => $roundId, 'name' => Rounds::whereKey($roundId)->value('name')],
            'total_exams' => count($slots),
            'average_percentage' => $total ? round(array_sum(array_column($rows, 'total_percentage_value')) / $total, 2) . '%' : '0%',
            'data' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ], 'success', 200);
    }

    /**
     * One student's completion in one round: the same response the student sees in
     * getMyCompeletenessRoundByRoundID, plus who the student is.
     */
    public function getStudentCompletion(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $enrollment = UserRounds::where('round_id', $data['round_id'])
            ->where('student_id', $data['student_id'])
            ->orderByDesc('id')
            ->first();

        if (!$enrollment) {
            return res_data('الطالب غير مشترك في هذه الدورة', 'not found', 404);
        }

        $student = Student::find($data['student_id'], ['id', 'name', 'phone', 'gender']);

        return res_data(array_merge(
            [
                'student' => $student->only(['id', 'name', 'phone', 'gender']),
                'round_name' => Rounds::whereKey($data['round_id'])->value('name'),
                'enrollment_status' => $enrollment->status,
            ],
            $this->completion->studentReport((int) $data['round_id'], (int) $data['student_id'])
        ), 'success', 200);
    }
}
