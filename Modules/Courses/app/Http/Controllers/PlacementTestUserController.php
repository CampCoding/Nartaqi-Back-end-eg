<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CategoryPartsModel;
use Modules\Courses\Models\PlacementTestModel;
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
        ]);

        $scoreRecord = PlacementTestStudentScoresModel::create($data);

        // Parse score (e.g., "10/20")
        $parts = explode('/', $data['score']);
        $studentDegree = isset($parts[0]) ? (int) $parts[0] : 0;

        // Find suggestion based on placement_test_id and degree range
        $suggestion = PlacementTestSuggestionModel::where('placement_test_id', $data['placement_test_id'])
            ->where('from_score', '<=', $studentDegree)
            ->where('to_score', '>=', $studentDegree)
            ->with(['suggestionRound' => function ($query) {
                $query->select('id', 'name', 'image');
            }])
            ->first();

        if ($suggestion && $suggestion->suggestionRound) {
            $suggestion->suggestionRound->setVisible(['id', 'name', 'image_url'])->setAppends(['image_url']);
        }

        return res_data([
            'score' => $scoreRecord,
            'suggestion' => $suggestion
        ], 'Score saved and suggestion retrieved', 200);
    }

    public function checkIfUserSolvedBefore(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'placement_test_id' => 'required|exists:placement_test,id',
        ]);

        $scoreRecord = PlacementTestStudentScoresModel::where('student_id', $data['student_id'])
            ->where('placement_test_id', $data['placement_test_id'])
            ->first();

        $isSolved = $scoreRecord ? true : false;
        $suggestion = null;

        if ($isSolved) {
            // Parse score (e.g., "10/20")
            $parts = explode('/', $scoreRecord->score);
            $studentDegree = isset($parts[0]) ? (int) $parts[0] : 0;

            // Find suggestion based on placement_test_id and degree range
            $suggestion = PlacementTestSuggestionModel::where('placement_test_id', $data['placement_test_id'])
                ->where('from_score', '<=', $studentDegree)
                ->where('to_score', '>=', $studentDegree)
                ->with(['suggestionRound' => function ($query) {
                    $query->select('id', 'name', 'image');
                }])
                ->first();

            if ($suggestion && $suggestion->suggestionRound) {
                $suggestion->suggestionRound->setVisible(['id', 'name', 'image_url'])->setAppends(['image_url']);
            }
        }

        return res_data([
            'is_solved' => $isSolved,
            'score' => $scoreRecord,
            'suggestion' => $suggestion
        ], 'Checked successfully', 200);
    }
}
