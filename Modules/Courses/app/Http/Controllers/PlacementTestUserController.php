<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\CategoryPartsModel;
use Modules\Courses\Models\PlacementTestAnswerModel;
use Modules\Courses\Models\PlacementTestModel;
use Modules\Courses\Models\PlacementTestQuestionsModel;
use Modules\Courses\Models\PlacementTestSectionsModel;
use Modules\Courses\Models\PlacementTestStudentScoresModel;
use Modules\Courses\Models\PlacementTestSuggestionModel;

class PlacementTestUserController extends Controller
{
    public function getCategoryParts(Request $request)
    {
        $categoryParts = CategoryPartsModel::whereHas('placement_test')
            ->orderBy('sort_as_placement_test', 'asc')
            ->get();
        return res_data($categoryParts, 'success', 200);
    }

    public function getPlacementTestByCategoryPart(Request $request)
    {
        $data = $request->validate([
            'category_part_id' => 'required|exists:category_parts,id',
        ]);

        $placementTest = PlacementTestModel::where('category_part_id', $data['category_part_id'])
            ->withCount('sections')
            ->first();

        if (!$placementTest) {
            return res_data([], 'Placement test not found', 404);
        }

        $placementTest->sections = PlacementTestSectionsModel::where('placement_test_id', $placementTest->id)
            ->withCount('questions')
            ->get();

        return res_data($placementTest, 'success', 200);
    }

    public function getSectionsByPlacementTest(Request $request)
    {
        $data = $request->validate([
            'placement_test_id' => 'required|exists:placement_test,id',
        ]);

        $sections = PlacementTestSectionsModel::where('placement_test_id', $data['placement_test_id'])->get();

        $sections->transform(function ($section) {
            $questions = $section->questions()
                ->with(['options', 'paragraph'])
                ->get();

            $formattedQuestions = [];
            $processedParagraphIds = [];

            foreach ($questions as $question) {
                if ($question->question_type === 'paragraph_mcq') {
                    $pId = $question->paragraph_id;
                    if (in_array($pId, $processedParagraphIds)) {
                        continue;
                    }
                    $processedParagraphIds[] = $pId;

                    $paragraphQuestions = $questions->where('paragraph_id', $pId);
                    $paragraphModel = $question->paragraph;

                    $formattedQuestions[] = [
                        'id' => $paragraphModel ? $paragraphModel->id : $pId,
                        'question_type' => 'paragraph',
                        'paragraph' => $paragraphModel ? [
                            'id' => $paragraphModel->id,
                            'placement_test_section_id' => $paragraphModel->placement_test_section_id,
                            'paragraph_content' => $paragraphModel->paragraph_content,
                        ] : null,
                        'questions' => $paragraphQuestions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'placement_test_section_id' => $q->placement_test_section_id,
                                'question_text' => $q->question_text,
                                'question_type' => $q->question_type,
                                'instructions' => $q->instructions,
                                'label' => $q->label,
                                'paragraph_id' => $q->paragraph_id,
                                'options' => $q->options,
                            ];
                        })->values()->toArray(),
                    ];
                } else {
                    $formattedQuestions[] = [
                        'id' => $question->id,
                        'placement_test_section_id' => $question->placement_test_section_id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'instructions' => $question->instructions,
                        'label' => $question->label,
                        'paragraph_id' => null,
                        'options' => $question->options,
                        'paragraph' => null,
                    ];
                }
            }

            return [
                'id' => $section->id,
                'placement_test_id' => $section->placement_test_id,
                'title' => $section->title,
                'description' => $section->description,
                'time_if_free' => $section->time_if_free,
                'questions' => $formattedQuestions,
                'questions_count' => count($formattedQuestions),
            ];
        });

        return res_data($sections, 'success', 200);
    }

    public function storeScore(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'placement_test_id' => 'required|exists:placement_test,id',
            'score' => 'required|string',
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|integer|distinct',
            'answers.*.option_id' => 'nullable|integer',
            'answers.*.answer_text' => 'nullable|string',
        ]);

        $answers = collect($data['answers'] ?? []);

        // Answers must be questions of this placement test, with options of those same questions.
        $questions = $answers->isEmpty() ? collect() : PlacementTestQuestionsModel::whereIn('id', $answers->pluck('question_id'))
            ->whereIn('placement_test_section_id', PlacementTestSectionsModel::where('placement_test_id', $data['placement_test_id'])->select('id'))
            ->with('options:id,question_id,is_correct')
            ->get()
            ->keyBy('id');

        foreach ($answers->values() as $index => $answer) {
            $question = $questions[$answer['question_id']] ?? null;
            if (!$question) {
                return res_data('السؤال رقم ' . ($index + 1) . ' في الإجابات مش تبع اختبار تحديد المستوى ده', 'خطأ في التحقق من البيانات', 422);
            }
            if (!empty($answer['option_id']) && !$question->options->contains('id', (int) $answer['option_id'])) {
                return res_data('الاختيار في الإجابة رقم ' . ($index + 1) . ' مش تبع السؤال بتاعها', 'خطأ في التحقق من البيانات', 422);
            }
        }

        // Every attempt is kept as its own row; the latest one is the student's current result.
        $scoreRecord = DB::transaction(function () use ($data, $answers, $questions) {
            $scoreRecord = PlacementTestStudentScoresModel::create(collect($data)->only(['student_id', 'placement_test_id', 'score'])->all());

            foreach ($answers as $answer) {
                $optionId = !empty($answer['option_id']) ? (int) $answer['option_id'] : null;
                $option = $optionId ? $questions[$answer['question_id']]->options->firstWhere('id', $optionId) : null;

                PlacementTestAnswerModel::create([
                    'score_id' => $scoreRecord->id,
                    'question_id' => (int) $answer['question_id'],
                    'option_id' => $optionId,
                    'answer_text' => $answer['answer_text'] ?? null,
                    'is_correct' => $option ? (bool) $option->is_correct : null,
                ]);
            }

            return $scoreRecord;
        });

        $attemptsCount = PlacementTestStudentScoresModel::where('student_id', $data['student_id'])
            ->where('placement_test_id', $data['placement_test_id'])
            ->count();

        return res_data([
            'score' => $scoreRecord,
            'suggestion' => PlacementTestSuggestionModel::forScore((int) $data['placement_test_id'], $data['score']),
            'attempt_number' => $attemptsCount,
            'attempts_count' => $attemptsCount,
            'answers_count' => $answers->count(),
        ], 'Score saved and suggestion retrieved', 200);
    }

    public function checkIfUserSolvedBefore(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'placement_test_id' => 'required|exists:placement_test,id',
        ]);

        $attempts = PlacementTestStudentScoresModel::where('student_id', $data['student_id'])
            ->where('placement_test_id', $data['placement_test_id']);

        // The latest attempt is the student's current result (the student may retake the test).
        $scoreRecord = (clone $attempts)->orderByDesc('id')->first();

        return res_data([
            'is_solved' => (bool) $scoreRecord,
            'score' => $scoreRecord,
            'suggestion' => $scoreRecord
                ? PlacementTestSuggestionModel::forScore((int) $data['placement_test_id'], $scoreRecord->score)
                : null,
            'attempts_count' => $attempts->count(),
        ], 'Checked successfully', 200);
    }
}
