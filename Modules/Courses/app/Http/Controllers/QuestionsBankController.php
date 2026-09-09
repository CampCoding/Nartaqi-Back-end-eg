<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\QuestionsBankModel;
use Modules\Courses\Models\QuestionsBankOptionsModel;
use Modules\Courses\Models\QuestionBankParagraphModel;
use Modules\Courses\Models\QuestionBankPartsModel;
use Modules\Courses\Models\QuestionBankBranchesModel;
use Modules\Courses\Models\QuestionBankSkillsModel;
use Modules\Courses\Http\Requests\StoreQuestionBankRequest;
use Modules\Courses\Http\Requests\UpdateQuestionBankRequest;
use Modules\Courses\Http\Requests\DeleteQuestionBankRequest;
use Modules\Courses\Http\Requests\ShowQuestionBankRequest;
use Illuminate\Support\Facades\DB;

class QuestionsBankController extends Controller
{
    /**
     * Display a listing of questions.
     */
    public function getAllQuestions(Request $request)
    {
        try {
            $skillsId = $request->input('question_bank_skills_id');

            $query = QuestionsBankModel::with(['options', 'paragraph']);

            if ($skillsId) {
                $query->where('question_bank_skills_id', $skillsId);
            }

            $questions = $query->orderBy('created_at', 'desc')->get();

            $formattedQuestions = [];
            $processedParagraphIds = [];

            foreach ($questions as $question) {
                if ($question->type === 'paragraph') {
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
                            'placement_test_section_id' => $paragraphModel->question_bank_skills_id,
                            'paragraph_content' => $paragraphModel->paragraph_content,
                        ] : null,
                        'questions' => $paragraphQuestions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'placement_test_section_id' => $q->question_bank_skills_id,
                                'question_text' => $q->question_text,
                                'question_type' => $q->question_type,
                                'instructions' => $q->instructions,
                                'label' => null,
                                'paragraph_id' => $q->paragraph_id,
                                'options' => $q->options,
                            ];
                        })->values()->toArray(),
                    ];
                } else {
                    $formattedQuestions[] = [
                        'id' => $question->id,
                        'placement_test_section_id' => $question->question_bank_skills_id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'instructions' => $question->instructions,
                        'label' => null,
                        'paragraph_id' => null,
                        'options' => $question->options,
                        'paragraph' => null,
                    ];
                }
            }

            return response()->json([
                'statusCode' => 200,
                'status' => 'success',
                'message' => [
                    'questions' => $formattedQuestions,
                    'questions_count' => count($formattedQuestions),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created question with options (supports MCQ and Paragraph).
     */
    public function StoreQuestionWithAnswers(StoreQuestionBankRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($data['type'] === 'mcq') {
                // Create single question
                $question = QuestionsBankModel::create([
                    'question_text' => $data['question_text'],
                    'question_type' => $data['question_type'],
                    'question_bank_skills_id' => $data['question_bank_skills_id'],
                    'instructions' => $data['instructions'] ?? null,
                    'type' => 'mcq',
                ]);

                // Options
                if (isset($data['mcq_array']) && is_array($data['mcq_array'])) {
                    foreach ($data['mcq_array'] as $mcq_option) {
                        QuestionsBankOptionsModel::create([
                            'question_id' => $question->id,
                            'option_text' => $mcq_option['answer'],
                            'is_correct' => $mcq_option['is_correct'] == '1' ? 1 : 0,
                        ]);
                    }
                }

                $question->load('options');
                $result = $question;
            } else {
                // Paragraph type
                // Create paragraph first
                $paragraph = QuestionBankParagraphModel::create([
                    'question_bank_skills_id' => $data['question_bank_skills_id'],
                    'paragraph_content' => $data['paragraph_content'],
                ]);

                $createdQuestions = [];
                if (isset($data['questions']) && is_array($data['questions'])) {
                    foreach ($data['questions'] as $qData) {
                        $subQuestion = QuestionsBankModel::create([
                            'question_text' => $qData['question_text'],
                            'question_type' => $data['question_type'],
                            'question_bank_skills_id' => $data['question_bank_skills_id'],
                            'instructions' => $qData['instructions'] ?? null,
                            'type' => 'paragraph',
                            'paragraph_id' => $paragraph->id,
                        ]);

                        // Options for sub-question
                        if (isset($qData['mcq_array']) && is_array($qData['mcq_array'])) {
                            foreach ($qData['mcq_array'] as $mcq_option) {
                                QuestionsBankOptionsModel::create([
                                    'question_id' => $subQuestion->id,
                                    'option_text' => $mcq_option['answer'],
                                    'is_correct' => $mcq_option['is_correct'] == '1' ? 1 : 0,
                                ]);
                            }
                        }
                        $subQuestion->load('options');
                        $createdQuestions[] = $subQuestion;
                    }
                }
                $result = [
                    'paragraph' => $paragraph,
                    'questions' => $createdQuestions
                ];
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء السؤال بنجاح',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إنشاء السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alias for StoreQuestionWithAnswers for backward compatibility if needed.
     */
    public function storeQuestion(StoreQuestionBankRequest $request)
    {
        return $this->StoreQuestionWithAnswers($request);
    }

    /**
     * Display the specified question.
     */
    public function showQuestion(ShowQuestionBankRequest $request)
    {
        try {
            $id = $request->validated()['id'];
            $question = QuestionsBankModel::with('options')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $question
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'السؤال غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified question.
     */
    public function updateQuestion(UpdateQuestionBankRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $id = $data['id'];

            $question = QuestionsBankModel::findOrFail($id);

            // Update Question text and type if provided
            $updateData = [];
            if (isset($data['question_text'])) {
                $updateData['question_text'] = $data['question_text'];
            }
            if (isset($data['question_type'])) {
                $updateData['question_type'] = $data['question_type'];
            }
            if (isset($data['question_bank_skills_id'])) {
                $updateData['question_bank_skills_id'] = $data['question_bank_skills_id'];
            }
            if (isset($data['instructions'])) {
                $updateData['instructions'] = $data['instructions'];
            }

            if (!empty($updateData)) {
                $question->update($updateData);
            }

            // Update options if provided
            if (isset($data['options'])) {
                // Delete old options
                QuestionsBankOptionsModel::where('question_id', $id)->delete();

                // Create new options
                foreach ($data['options'] as $optionData) {
                    QuestionsBankOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct']
                    ]);
                }
            }

            // Load the question with options
            $question->load('options');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث السؤال بنجاح',
                'data' => $question->fresh(['options'])
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'السؤال غير موجود'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تحديث السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified question and its options.
     */
    public function deleteQuestion(DeleteQuestionBankRequest $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->validated()['id'];
            $question = QuestionsBankModel::findOrFail($id);

            // Delete associated options
            QuestionsBankOptionsModel::where('question_id', $id)->delete();

            // Delete the question
            $question->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف السؤال بنجاح'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'السؤال غير موجود'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حذف السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get questions by type.
     */
    // ignore following 
    public function getQuestionsByType(Request $request)
    {
        try {
            $request->validate([
                'question_type' => 'required|string|in:mcq,true_false,short_answer'
            ], [
                'question_type.required' => 'نوع السؤال مطلوب',
                'question_type.in' => 'نوع السؤال يجب أن يكون: mcq, true_false, أو short_answer'
            ]);

            $perPage = $request->input('per_page', 10);
            $questionType = $request->input('question_type');

            $questions = QuestionsBankModel::with('options')
                ->where('question_type', $questionType)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $questions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب الأسئلة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified paragraph.
     */
    public function editParagraphQuestionsBank(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:question_bank_paragraphs,id',
                'paragraph_content' => 'required|string'
            ]);

            $paragraph = QuestionBankParagraphModel::findOrFail($data['id']);
            $paragraph->update([
                'paragraph_content' => $data['paragraph_content'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث الفقرة بنجاح',
                'data' => $paragraph
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تحديث الفقرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function storeQbankParagraphQuestionWithAnswers(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'question_bank_skills_id' => 'required',
                'paragraph_id' => 'required|exists:question_bank_paragraphs,id',
                'question_text' => 'required|string',
                'question_type' => 'required|string',
                'mcq_array' => 'required|array',
            ]);

            $question = QuestionsBankModel::create([
                'question_bank_skills_id' => $request->question_bank_skills_id,
                'paragraph_id' => $request->paragraph_id,
                'question_text' => $request->question_text,
                'question_type' => $request->question_type,
                'instructions' => $request->instructions ?? null,
                'type' => 'paragraph',
            ]);

            if ($request->has('mcq_array') && is_array($request->mcq_array)) {
                foreach ($request->mcq_array as $mcq_option) {
                    QuestionsBankOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $mcq_option['answer'],
                        'is_correct' => $mcq_option['is_correct'] == '1' ? 1 : 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم إضافة السؤال إلى الفقرة بنجاح',
                'data' => $question->load('options')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إضافة السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified paragraph and its questions.
     */
    public function deleteParagraphQuestionsBank(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id' => 'required|exists:question_bank_paragraphs,id',
            ]);

            $paragraph = QuestionBankParagraphModel::findOrFail($data['id']);

            // Get questions associated with this paragraph
            $questions = QuestionsBankModel::where('paragraph_id', $paragraph->id)->get();

            foreach ($questions as $question) {
                // Delete options
                QuestionsBankOptionsModel::where('question_id', $question->id)->delete();
                // Delete question
                $question->delete();
            }

            // Delete paragraph
            $paragraph->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف الفقرة والأسئلة المرتبطة بها بنجاح'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حذف الفقرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionBankParts(Request $request)
    {
        try {
            $parts = QuestionBankPartsModel::all();
            return response()->json([
                'status' => 'success',
                'data' => $parts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب الأقسام',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionBankBranchesByPartId(Request $request)
    {
        try {
            $request->validate([
                'question_bank_parts_id' => 'required|exists:question_bank_parts,id'
            ]);

            $branches = QuestionBankBranchesModel::where('question_bank_parts_id', $request->question_bank_parts_id)->get();
            return response()->json([
                'status' => 'success',
                'data' => $branches
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب الفروع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionBankSkillsByBranchId(Request $request)
    {
        try {
            $request->validate([
                'question_bank_branch_id' => 'required|exists:question_bank_branches,id'
            ]);

            $skills = QuestionBankSkillsModel::where('question_bank_branch_id', $request->question_bank_branch_id)
                ->get()
                ->map(function ($skill) {
                    $mcqCount = QuestionsBankModel::where('question_bank_skills_id', $skill->id)
                        ->where('type', 'mcq')
                        ->count();

                    $paragraphCount = QuestionBankParagraphModel::where('question_bank_skills_id', $skill->id)
                        ->count();

                    $skill->mcq_count = $mcqCount;
                    $skill->paragraph_count = $paragraphCount;

                    return $skill;
                });

            return response()->json([
                'status' => 'success',
                'data' => $skills
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب المهارات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
