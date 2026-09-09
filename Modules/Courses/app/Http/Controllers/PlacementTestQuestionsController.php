<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Modules\Courses\Models\PlacementTestQuestionsModel;
use Modules\Courses\Models\PlacementTestParagraphsModel;
use Modules\Courses\Models\PlacementTestQuestionOptionsModel;
use Illuminate\Support\Facades\Log;
use Modules\Courses\Models\QuestionsBankModel;
use Modules\Courses\Models\QuestionBankParagraphModel;

class PlacementTestQuestionsController extends Controller
{
    public function getPlacementTestQuestions(Request $request)
    {
        try {
            // Get all questions with their relationships
            $questions = PlacementTestQuestionsModel::where('placement_test_section_id', $request->placement_test_section_id)
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

            return res_data([
                'questions' => $formattedQuestions,
                'questions_count' => count($formattedQuestions),
            ], 'Questions fetched successfully', 200);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'Unknown error';
            return res_data($errorMessage, 'Error fetching questions', 500);
        }
    }

    public function storePlacementTestQuestionWithAnswers(Request $request)
    {
        try {
            if ($request->question_type == 'mcq' || $request->question_type == 't_f') {
                $question = PlacementTestQuestionsModel::create([
                    'placement_test_section_id' => $request->placement_test_section_id,
                    'question_text' => $request->question_text,
                    'question_type' => $request->question_type,
                    'instructions' => $request->instructions,
                    'label' => $request->label,
                ]);

                if (!$question) {
                    return res_data(null, 'Failed to create question', 400);
                }

                if ($request->has('mcq_array') && is_array($request->mcq_array)) {
                    foreach ($request->mcq_array as $mcq_option) {
                        PlacementTestQuestionOptionsModel::create([
                            'question_id' => $question->id,
                            'option_text' => $mcq_option['answer'],
                            'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                        ]);
                    }
                    return res_data($question, 'Question added successfully', 200);
                }
                return res_data(null, 'mcq_array is required for question type mcq', 400);
            } elseif ($request->question_type == 'paragraph_mcq') {
                // Create the paragraph content first
                $paragraph = PlacementTestParagraphsModel::create([
                    'placement_test_section_id' => $request->placement_test_section_id,
                    'paragraph_content' => $request->paragraph_content,
                ]);

                $createdQuestions = [];

                if ($request->has('questions') && is_array($request->questions)) {
                    foreach ($request->questions as $questionData) {
                        $subQuestion = PlacementTestQuestionsModel::create([
                            'placement_test_section_id' => $request->placement_test_section_id,
                            'question_text' => $questionData['question_text'],
                            'question_type' => 'paragraph_mcq',
                            'instructions' => $questionData['instructions'] ?? null,
                            'label' => $questionData['label'] ?? null,
                            'paragraph_id' => $paragraph->id,
                        ]);

                        if (isset($questionData['mcq_array']) && is_array($questionData['mcq_array'])) {
                            foreach ($questionData['mcq_array'] as $mcq_option) {
                                PlacementTestQuestionOptionsModel::create([
                                    'question_id' => $subQuestion->id,
                                    'option_text' => $mcq_option['answer'],
                                    'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                                ]);
                            }
                        }
                        $createdQuestions[] = $subQuestion;
                    }
                }

                return res_data([
                    'paragraph' => $paragraph,
                    'questions' => $createdQuestions
                ], 'Paragraph and questions added successfully', 200);
            }

            return res_data(null, 'Unsupported question type', 400);
        } catch (\Exception $e) {
            Log::error('storePlacementTestQuestionWithAnswers Error: ' . $e->getMessage());
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function storePlacementTestParagraphQuestionWithAnswers(Request $request)
    {
        try {
            $question = PlacementTestQuestionsModel::create([
                'placement_test_section_id' => $request->placement_test_section_id,
                'question_text' => $request->question_text,
                'question_type' => 'paragraph_mcq',
                'instructions' => $request->instructions,
                'label' => $request->label,
                'paragraph_id' => $request->paragraph_id,
            ]);

            if (!$question) {
                return res_data(null, 'Failed to create question', 400);
            }

            if (isset($request->mcq_array) && is_array($request->mcq_array)) {
                foreach ($request->mcq_array as $mcq_option) {
                    PlacementTestQuestionOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $mcq_option['answer'],
                        'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                    ]);
                }
            }

            return res_data($question->load('options'), 'Question added to paragraph successfully', 200);
        } catch (\Exception $e) {
            Log::error('storePlacementTestParagraphQuestionWithAnswers Error: ' . $e->getMessage());
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function editPlacementTestQuestion(Request $request)
    {
        try {
            $question = PlacementTestQuestionsModel::find($request->id);
            if (!$question) {
                return res_data(null, 'Question not found', 404);
            }

            $question->update([
                'question_text' => $request->question_text,
                'instructions' => $request->instructions,
                'label' => $request->label,
            ]);

            PlacementTestQuestionOptionsModel::where('question_id', $question->id)->delete();

            if ($request->has('mcq_array') && is_array($request->mcq_array)) {
                foreach ($request->mcq_array as $mcq_option) {
                    PlacementTestQuestionOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $mcq_option['answer'],
                        'is_correct' => $mcq_option['correct_or_not'] == "1" ? 1 : 0,
                    ]);
                }
            }

            return res_data($question->load('options'), 'Question updated successfully', 200);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function editPlacementTestParagraph(Request $request)
    {
        try {
            $paragraph = PlacementTestParagraphsModel::find($request->id);
            if (!$paragraph) {
                return res_data(null, 'Paragraph not found', 404);
            }
            $paragraph->update([
                'paragraph_content' => $request->paragraph_content,
            ]);
            return res_data($paragraph, 'Paragraph updated successfully', 200);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function deletePlacementTestParagraph(Request $request)
    {
        try {
            $paragraph = PlacementTestParagraphsModel::find($request->id);
            if (!$paragraph) {
                return res_data(null, 'Paragraph not found', 404);
            }

            $questions = PlacementTestQuestionsModel::where('paragraph_id', $paragraph->id)->get();
            foreach ($questions as $question) {
                PlacementTestQuestionOptionsModel::where('question_id', $question->id)->delete();
                $question->delete();
            }
            $paragraph->delete();
            return res_data('Paragraph and associated questions deleted successfully', 'success', 200);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function deletePlacementTestQuestion(Request $request)
    {
        try {
            $question = PlacementTestQuestionsModel::find($request->id);
            if (!$question) {
                return res_data(null, 'Question not found', 404);
            }

            PlacementTestQuestionOptionsModel::where('question_id', $question->id)->delete();
            $question->delete();
            return res_data('Question deleted successfully', 'success', 200);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function makeAutoGenerateQuestions(Request $request)
    {
        try {
            $request->validate([
                'placement_test_section_id' => 'required|exists:placement_test_sections,id',
                'question_bank_skills_id' => 'required|exists:question_bank_skills,id',
                'questions_count' => 'nullable|integer|min:0',
                'paragraphs_count' => 'nullable|integer|min:0',
            ]);

            $sectionId = $request->input('placement_test_section_id');
            $skillId = $request->input('question_bank_skills_id');
            $questionsCount = $request->input('questions_count', 0);
            $paragraphsCount = $request->input('paragraphs_count', 0);

            if ($questionsCount == 0 && $paragraphsCount == 0) {
                return res_data(null, 'يجب تحديد عدد الأسئلة أو عدد القطع', 400);
            }

            $copiedQuestions = [];
            $copiedParagraphs = [];

            // 1. Generate MCQ questions if requested
            if ($questionsCount > 0) {
                // Get question texts already used in this placement test section (excluding paragraph questions)
                $usedQuestionTexts = PlacementTestQuestionsModel::where('placement_test_section_id', $sectionId)
                    ->whereNull('paragraph_id')
                    ->whereNotNull('question_text')
                    ->pluck('question_text')
                    ->toArray();

                $usedCounts = array_count_values($usedQuestionTexts);

                // Get all questions from question bank to handle duplicates properly
                $allBankQuestions = QuestionsBankModel::with('options')
                    ->where('question_bank_skills_id', $skillId)
                    ->where('type', 'mcq')
                    ->get();
                
                $availableBankQuestions = collect();
                foreach ($allBankQuestions as $bq) {
                    if (isset($usedCounts[$bq->question_text]) && $usedCounts[$bq->question_text] > 0) {
                        $usedCounts[$bq->question_text]--;
                        continue;
                    }
                    $availableBankQuestions->push($bq);
                }

                if ($availableBankQuestions->count() < $questionsCount) {
                    return res_data([
                        'required_mcq' => (int)$questionsCount,
                        'available_mcq' => $availableBankQuestions->count(),
                        'already_used_in_section' => count($usedQuestionTexts)
                    ], 'عدد أسئلة الاختيارات المتاحة في بنك الأسئلة غير كافٍ', 400);
                }

                // Pick random questions from available ones
                $selectedBankQuestions = $availableBankQuestions->random($questionsCount);

                foreach ($selectedBankQuestions as $bankQuestion) {
                    // Create placement test question
                    $placementQuestion = PlacementTestQuestionsModel::create([
                        'placement_test_section_id' => $sectionId,
                        'question_text' => $bankQuestion->question_text,
                        'question_type' => 'mcq',
                        'instructions' => $bankQuestion->instructions,
                        'label' => null,
                        'paragraph_id' => null,
                    ]);

                    // Copy question options
                    foreach ($bankQuestion->options as $option) {
                        PlacementTestQuestionOptionsModel::create([
                            'question_id' => $placementQuestion->id,
                            'option_text' => $option->option_text,
                            'is_correct' => $option->is_correct ? '1' : '0'
                        ]);
                    }

                    $copiedQuestions[] = $placementQuestion;
                }
            }

            // 2. Generate Paragraph questions if requested
            if ($paragraphsCount > 0) {
                // Get already used paragraph contents in this section
                $usedParagraphContents = PlacementTestParagraphsModel::where('placement_test_section_id', $sectionId)
                    ->whereNotNull('paragraph_content')
                    ->pluck('paragraph_content')
                    ->toArray();

                $usedParagraphCounts = array_count_values($usedParagraphContents);

                // Get all paragraphs from question bank
                $allBankParagraphs = QuestionBankParagraphModel::with(['questions.options'])
                    ->where('question_bank_skills_id', $skillId)
                    ->get();
                
                $availableBankParagraphs = collect();
                foreach ($allBankParagraphs as $bp) {
                    if (isset($usedParagraphCounts[$bp->paragraph_content]) && $usedParagraphCounts[$bp->paragraph_content] > 0) {
                        $usedParagraphCounts[$bp->paragraph_content]--;
                        continue;
                    }
                    $availableBankParagraphs->push($bp);
                }

                if ($availableBankParagraphs->count() < $paragraphsCount) {
                    return res_data([
                        'required_paragraphs' => (int)$paragraphsCount,
                        'available_paragraphs' => $availableBankParagraphs->count(),
                        'already_used_paragraphs_in_section' => count($usedParagraphContents)
                    ], 'عدد القطع المتاحة في بنك الأسئلة غير كافٍ', 400);
                }

                // Pick random paragraphs from available ones
                $selectedBankParagraphs = $availableBankParagraphs->random($paragraphsCount);

                foreach ($selectedBankParagraphs as $bankParagraph) {
                    // Create placement test paragraph
                    $placementParagraph = PlacementTestParagraphsModel::create([
                        'placement_test_section_id' => $sectionId,
                        'paragraph_content' => $bankParagraph->paragraph_content,
                    ]);

                    // Copy questions for this paragraph
                    foreach ($bankParagraph->questions as $bankQuestion) {
                        $placementQuestion = PlacementTestQuestionsModel::create([
                            'placement_test_section_id' => $sectionId,
                            'question_text' => $bankQuestion->question_text,
                            'question_type' => 'paragraph_mcq',
                            'instructions' => $bankQuestion->instructions,
                            'label' => null,
                            'paragraph_id' => $placementParagraph->id,
                        ]);

                        // Copy options
                        foreach ($bankQuestion->options as $option) {
                            PlacementTestQuestionOptionsModel::create([
                                'question_id' => $placementQuestion->id,
                                'option_text' => $option->option_text,
                                'is_correct' => $option->is_correct ? '1' : '0'
                            ]);
                        }
                    }

                    $copiedParagraphs[] = $placementParagraph;
                }
            }

            return res_data([
                'questions_count' => count($copiedQuestions),
                'paragraphs_count' => count($copiedParagraphs),
                'placement_test_section_id' => $sectionId,
            ], 'تم إنشاء الأسئلة والقطع بنجاح', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->errors(), 'Validation Error', 422);
        } catch (\Exception $e) {
            Log::error('makeAutoGenerateQuestions Error: ' . $e->getMessage());
            return res_data($e->getMessage(), 'error', 500);
        }
    }
}
