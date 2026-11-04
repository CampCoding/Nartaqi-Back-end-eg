<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Models\Student;
use Modules\Courses\Models\PlacementTestAnswerModel;
use Modules\Courses\Models\PlacementTestModel;
use Modules\Courses\Models\PlacementTestQuestionsModel;
use Modules\Courses\Models\PlacementTestSectionsModel;
use Modules\Courses\Models\PlacementTestStudentScoresModel;
use Modules\Courses\Models\PlacementTestSuggestionModel;

/**
 * Placement test results for the admin. A student can take a placement test many times; every attempt
 * is a row in placement_test_scores and the latest one is the student's current result.
 */
class AdminPlacementTestScoresController extends Controller
{
    /**
     * One row per (student, placement test) with how many times the student took it and the last score.
     */
    public function getPlacementTestStudents(Request $request)
    {
        $data = $request->validate([
            'placement_test_id' => 'nullable|exists:placement_test,id',
            'category_part_id' => 'nullable|exists:category_parts,id',
            'student_id' => 'nullable|exists:students,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $like = filled($data['search'] ?? null) ? '%' . addcslashes($data['search'], '\\%_') . '%' : null;

        $rows = PlacementTestStudentScoresModel::query()
            ->selectRaw('student_id, placement_test_id, COUNT(*) as attempts_count, MAX(id) as last_attempt_id, MIN(created_at) as first_attempt_at, MAX(created_at) as last_attempt_at')
            ->when($data['placement_test_id'] ?? null, fn ($q, $id) => $q->where('placement_test_id', $id))
            ->when($data['category_part_id'] ?? null, fn ($q, $id) => $q->whereIn(
                'placement_test_id',
                PlacementTestModel::where('category_part_id', $id)->select('id')
            ))
            ->when($data['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($like, fn ($q) => $q->whereIn(
                'student_id',
                Student::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like))->select('id')
            ))
            ->groupBy('student_id', 'placement_test_id')
            ->orderByDesc('last_attempt_id')
            ->paginate((int) ($data['per_page'] ?? 20));

        $items = collect($rows->items());
        $lastAttempts = PlacementTestStudentScoresModel::whereIn('id', $items->pluck('last_attempt_id'))->get()->keyBy('id');
        $students = Student::whereIn('id', $items->pluck('student_id')->unique())->get(['id', 'name', 'phone', 'gender'])->keyBy('id');
        $tests = PlacementTestModel::whereIn('id', $items->pluck('placement_test_id')->unique())->get(['id', 'title', 'category_part_id'])->keyBy('id');
        $suggestions = $this->suggestionsByTest($items->pluck('placement_test_id')->unique()->all());

        $rows->setCollection($items->map(function ($row) use ($lastAttempts, $students, $tests, $suggestions) {
            $last = $lastAttempts[$row->last_attempt_id] ?? null;
            $student = $students[$row->student_id] ?? null;
            $test = $tests[$row->placement_test_id] ?? null;

            return [
                'student' => $student ? $student->only(['id', 'name', 'phone', 'gender']) : ['id' => (int) $row->student_id],
                'placement_test' => $test ? $test->only(['id', 'title', 'category_part_id']) : ['id' => (int) $row->placement_test_id],
                'attempts_count' => (int) $row->attempts_count,
                'last_score' => $last?->score,
                'last_attempt_id' => (int) $row->last_attempt_id,
                'last_attempt_at' => $last?->created_at?->toDateTimeString(),
                'first_attempt_at' => $row->first_attempt_at,
                'last_suggestion' => $last ? $this->matchSuggestion($suggestions[$row->placement_test_id] ?? collect(), $last->score) : null,
            ];
        }));

        return res_data($rows, 'success', 200);
    }

    /**
     * Every attempt of one student (optionally for one placement test), newest first, each with its
     * attempt number, score, date and the suggested round for that score.
     */
    public function getStudentPlacementTestAttempts(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'placement_test_id' => 'nullable|exists:placement_test,id',
        ]);

        $attempts = PlacementTestStudentScoresModel::where('student_id', $data['student_id'])
            ->when($data['placement_test_id'] ?? null, fn ($q, $id) => $q->where('placement_test_id', $id))
            ->withCount([
                'answers',
                'answers as correct_answers_count' => fn ($q) => $q->where('is_correct', true),
                'answers as wrong_answers_count' => fn ($q) => $q->where('is_correct', false),
            ])
            ->orderBy('id')
            ->get();

        $tests = PlacementTestModel::whereIn('id', $attempts->pluck('placement_test_id')->unique())->get(['id', 'title', 'category_part_id'])->keyBy('id');
        $suggestions = $this->suggestionsByTest($attempts->pluck('placement_test_id')->unique()->all());

        $result = $attempts->groupBy('placement_test_id')->map(function ($testAttempts, $testId) use ($tests, $suggestions) {
            $numbered = $testAttempts->values()->map(fn ($attempt, $index) => [
                'id' => $attempt->id,
                'attempt_number' => $index + 1,
                'score' => $attempt->score,
                'created_at' => $attempt->created_at?->toDateTimeString(),
                'answers_count' => (int) $attempt->answers_count,
                'correct_answers_count' => (int) $attempt->correct_answers_count,
                'wrong_answers_count' => (int) $attempt->wrong_answers_count,
                'suggestion' => $this->matchSuggestion($suggestions[$testId] ?? collect(), $attempt->score),
            ]);
            $test = $tests[$testId] ?? null;

            return [
                'placement_test' => $test ? $test->only(['id', 'title', 'category_part_id']) : ['id' => (int) $testId],
                'attempts_count' => $numbered->count(),
                'last_score' => $numbered->last()['score'],
                'attempts' => $numbered->reverse()->values(),
            ];
        })->sortByDesc(fn ($test) => $test['attempts'][0]['id'])->values();

        return res_data([
            'student' => Student::find($data['student_id'], ['id', 'name', 'phone', 'gender'])->only(['id', 'name', 'phone', 'gender']),
            'placement_tests' => $result,
        ], 'success', 200);
    }

    /**
     * One attempt in full: the placement test's sections and questions (paragraph questions grouped as in
     * getSectionsByPlacementTest), each with its options, the correct option and what the student answered.
     * attempt_id is the attempt's id from getStudentPlacementTestAttempts (attempts[].id).
     */
    public function getPlacementTestAttemptAnswers(Request $request)
    {
        $data = $request->validate([
            'attempt_id' => 'required|exists:placement_test_scores,id',
        ]);

        $attempt = PlacementTestStudentScoresModel::find($data['attempt_id']);
        $answers = PlacementTestAnswerModel::where('score_id', $attempt->id)->get()->keyBy('question_id');

        $attemptNumber = PlacementTestStudentScoresModel::where('student_id', $attempt->student_id)
            ->where('placement_test_id', $attempt->placement_test_id)
            ->where('id', '<=', $attempt->id)
            ->count();

        $formatQuestion = function ($question) use ($answers) {
            $answer = $answers[$question->id] ?? null;
            $correctOption = $question->options->first(fn ($o) => (bool) $o->is_correct);

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'instructions' => $question->instructions,
                'label' => $question->label,
                'paragraph_id' => $question->paragraph_id,
                'options' => $question->options->map(fn ($o) => [
                    'id' => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct' => (bool) $o->is_correct,
                    'is_student_choice' => $answer && (int) $answer->option_id === $o->id,
                ])->values(),
                'correct_option_id' => $correctOption?->id,
                'student_answer' => $answer ? [
                    'option_id' => $answer->option_id,
                    'answer_text' => $answer->answer_text,
                    'is_correct' => $answer->is_correct,
                ] : null,
            ];
        };

        $sections = PlacementTestSectionsModel::where('placement_test_id', $attempt->placement_test_id)->orderBy('id')->get()
            ->map(function ($section) use ($formatQuestion) {
                $questions = PlacementTestQuestionsModel::where('placement_test_section_id', $section->id)
                    ->with(['options', 'paragraph'])
                    ->orderBy('id')
                    ->get();

                $items = [];
                $seenParagraphs = [];
                foreach ($questions as $question) {
                    if ($question->question_type !== 'paragraph_mcq') {
                        $items[] = ['type' => 'question'] + $formatQuestion($question);
                        continue;
                    }
                    if (isset($seenParagraphs[$question->paragraph_id])) {
                        continue;
                    }
                    $seenParagraphs[$question->paragraph_id] = true;
                    $paragraph = $question->paragraph;

                    $items[] = [
                        'type' => 'paragraph',
                        'id' => $paragraph ? $paragraph->id : $question->paragraph_id,
                        'paragraph_content' => $paragraph?->paragraph_content,
                        'questions' => $questions->where('paragraph_id', $question->paragraph_id)->map($formatQuestion)->values(),
                    ];
                }

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'questions' => $items,
                ];
            });

        $questionsCount = PlacementTestQuestionsModel::whereIn('placement_test_section_id', $sections->pluck('id'))->count();
        $test = PlacementTestModel::find($attempt->placement_test_id, ['id', 'title', 'category_part_id']);
        $student = Student::find($attempt->student_id, ['id', 'name', 'phone', 'gender']);

        return res_data([
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attemptNumber,
                'score' => $attempt->score,
                'created_at' => $attempt->created_at?->toDateTimeString(),
            ],
            'student' => $student?->only(['id', 'name', 'phone', 'gender']),
            'placement_test' => $test?->only(['id', 'title', 'category_part_id']),
            'summary' => [
                'questions_count' => $questionsCount,
                'answered_count' => $answers->count(),
                // Strict: an essay answer has is_correct = null and is neither right nor wrong.
                'correct_count' => $answers->whereStrict('is_correct', true)->count(),
                'wrong_count' => $answers->whereStrict('is_correct', false)->count(),
                'unanswered_count' => max(0, $questionsCount - $answers->count()),
            ],
            'sections' => $sections,
        ], 'success', 200);
    }

    /**
     * placement_test_id => its suggestions (with the suggested round), loaded once per request.
     */
    private function suggestionsByTest(array $testIds)
    {
        return PlacementTestSuggestionModel::whereIn('placement_test_id', $testIds)
            ->with(['suggestionRound' => fn ($q) => $q->select('id', 'name', 'image')])
            ->get()
            ->groupBy('placement_test_id');
    }

    private function matchSuggestion($testSuggestions, ?string $score): ?array
    {
        $degree = PlacementTestStudentScoresModel::degreeOf($score);
        $suggestion = $testSuggestions->first(fn ($s) => $s->from_score <= $degree && $s->to_score >= $degree);

        if (!$suggestion) {
            return null;
        }

        $round = $suggestion->suggestionRound;

        return [
            'id' => $suggestion->id,
            'from_score' => $suggestion->from_score,
            'to_score' => $suggestion->to_score,
            'message' => $suggestion->message,
            'round' => $round ? ['id' => $round->id, 'name' => $round->name, 'image_url' => $round->image_url] : null,
        ];
    }
}
