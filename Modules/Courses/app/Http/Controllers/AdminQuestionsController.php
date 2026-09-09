<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Courses\Http\Requests\AddQuestionRequest;
use Modules\Courses\Http\Requests\AddParagraphQuestionRequest;
use Modules\Courses\Http\Requests\GetQuestionsRequest;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\QuestionAnswersModel;
use Modules\Courses\Models\QuestionOptionsModel;
use Modules\Courses\Models\QuestionParagraphsModel;
use Modules\Courses\Models\AdminParagraphMcqQuestionsModel;
use Modules\Courses\Models\QuestionsBankModel;
use Modules\Courses\Models\QuestionBankParagraphModel;
use Illuminate\Support\Facades\Storage;

class AdminQuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_questions(GetQuestionsRequest $request)
    {
        try {
            $data = $request->validated();

            // Get all questions with their relationships
            $questions = AdminQuestionsModel::where('exam_section_id', $data['exam_section_id'])
                ->with(['options', 'paragraph'])
                ->orderBy('id', 'asc')
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
                            'exam_section_id' => $paragraphModel->exam_section_id,
                            'paragraph_content' => $paragraphModel->paragraph_content,
                            'voice' => $paragraphModel->voice,
                            'description' => $paragraphModel->description,
                        ] : null,
                        'questions' => $paragraphQuestions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'exam_section_id' => $q->exam_section_id,
                                'question_text' => $q->question_text,
                                'question_type' => $q->question_type,
                                'instructions' => $q->instructions,
                                'paragraph_id' => $q->paragraph_id,
                                'options' => $q->options,
                            ];
                        })->values()->toArray(),
                    ];
                } else {
                    $formattedQuestions[] = [
                        'id' => $question->id,
                        'exam_section_id' => $question->exam_section_id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'instructions' => $question->instructions,
                        'paragraph_id' => null,
                        'options' => $question->options,
                        'description' => $question->description,
                        'paragraph' => null,
                    ];
                }
            }

            return res_data([
                'questions' => $formattedQuestions,
            ], 'تم جلب الأسئلة بنجاح', 200);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'خطأ غير معروف';
            return res_data($errorMessage, 'حدث خطأ أثناء جلب الأسئلة', 500);
        }
    }
    public function StoreQuestionWithAnswers(AddQuestionRequest $request)
    {
        try {
            $data = $request->validated();

            // For paragraph_mcq, skip initial question creation
            if ($request->question_type == 'paragraph_mcq') {
                // Handle paragraph_mcq separately
            } else {
                $question = AdminQuestionsModel::create($data);
                if (!$question) {
                    return res_data(null, 'فشل إنشاء السؤال', 400);
                }
            }
            if ($request->question_type == 'mcq' || $request->question_type == 't_f') {
                if ($request->has('mcq_array') && is_array($request->mcq_array)) {
                    foreach ($request->mcq_array as $mcq_option) {
                        $mcq = QuestionOptionsModel::create([
                            'question_id' => $question->id,
                            'option_text' => $mcq_option['answer'],
                            'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                            'question_explanation' => !empty($mcq_option['question_explanation']) ? $mcq_option['question_explanation'] : '',
                        ]);
                    }
                    return res_data($question, 'تم إضافة السؤال بنجاح', 200);
                }
                return res_data(null, 'mcq_array مطلوب لنوع السؤال mcq', 400);
            } elseif ($request->question_type == 'paragraph_mcq') {
                // Create the paragraph content first
                $paragraph_mcq = QuestionParagraphsModel::create([
                    'exam_section_id' => $data['exam_section_id'],
                    'paragraph_content' => $request->paragraph_content,
                    'voice' => $request->voice,
                    'description' => $request->description,
                ]);

                // Now create multiple questions in AdminQuestionsModel for this paragraph
                $createdQuestions = [];

                if ($request->has('questions') && is_array($request->questions)) {
                    foreach ($request->questions as $index => $questionData) {
                        // Create each question with the paragraph_id from the created paragraph
                        $subQuestion = AdminQuestionsModel::create([
                            'exam_section_id' => $data['exam_section_id'],
                            'question_text' => $questionData['question_text'],
                            'question_type' => 'paragraph_mcq',
                            'instructions' => $questionData['instructions'],
                            'paragraph_id' => $paragraph_mcq->id,
                        ]);

                        // Create MCQ options for each question
                        if (isset($questionData['mcq_array']) && is_array($questionData['mcq_array'])) {
                            foreach ($questionData['mcq_array'] as $mcq_option) {
                                QuestionOptionsModel::create([
                                    'question_id' => $subQuestion->id,
                                    'option_text' => $mcq_option['answer'],
                                    'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                                    'question_explanation' => !empty($mcq_option['question_explanation']) ? $mcq_option['question_explanation'] : '',
                                ]);
                            }
                        }

                        $createdQuestions[] = $subQuestion;
                    }
                }

                return res_data([
                    'paragraph' => $paragraph_mcq,
                    'questions' => $createdQuestions
                ], 'تم إضافة الفقرة والأسئلة بنجاح', 200);
            }

            return res_data(null, 'نوع السؤال غير مدعوم', 400);
        } catch (\Exception $e) {
            Log::error('StoreQuestionWithAnswers Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return res_data('حدث خطأ أثناء إضافة السؤال: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * Add a single question to an existing paragraph by paragraph_id
     */
    public function StoreParagraphQuestionWithAnswers(AddParagraphQuestionRequest $request)
    {
        try {
            $data = $request->validated();

            // Check if the paragraph exists
            $paragraph = QuestionParagraphsModel::find($data['paragraph_id']);
            if (!$paragraph) {
                return res_data(null, 'الفقرة غير موجودة', 404);
            }

            // Shared description belongs to the paragraph, not each question in it
            if (array_key_exists('description', $data)) {
                $paragraph->update(['description' => $data['description']]);
            }

            // Create the question with paragraph_id
            $question = AdminQuestionsModel::create([
                'exam_section_id' => $data['exam_section_id'],
                'question_text' => $data['question_text'],
                'question_type' => 'paragraph_mcq',
                'instructions' => $data['instructions'],
                'paragraph_id' => $data['paragraph_id'],
            ]);


            if (!$question) {
                return res_data(null, 'فشل إنشاء السؤال', 400);
            }

            // Create MCQ options for the question
            if (isset($data['mcq_array']) && is_array($data['mcq_array'])) {
                foreach ($data['mcq_array'] as $mcq_option) {
                    QuestionOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $mcq_option['answer'],
                        'is_correct' => $mcq_option['correct_or_not'] == '1' ? '1' : '0',
                        'question_explanation' => !empty($mcq_option['question_explanation']) ? $mcq_option['question_explanation'] : '',
                    ]);
                }
            }

            // Load the options relationship before returning
            $question->load('options');

            return res_data($question, 'تم إضافة السؤال إلى الفقرة بنجاح', 200);
        } catch (\Exception $e) {
            Log::error('StoreParagraphQuestionWithAnswers Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return res_data('حدث خطأ أثناء إضافة السؤال: ' . $e->getMessage(), 'error', 500);
        }
    }
    public function editQuestion(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:questions,id',
                'question_text' => 'required|string',
                'instructions' => 'nullable',
                'mcq_array' => 'required|array',
                'mcq_array.*.answer' => 'required|string',
                'mcq_array.*.correct_or_not' => 'required|in:0,1',
                'mcq_array.*.question_explanation' => 'nullable|string',
                'description' => 'nullable',
            ]);

            $question = AdminQuestionsModel::find($data['id']);

            $question->update([
                'question_text' => $data['question_text'],
                'instructions' => $data['instructions'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            QuestionOptionsModel::where('question_id', $question->id)->delete();

            foreach ($data['mcq_array'] as $mcq_option) {
                QuestionOptionsModel::create([
                    'question_id' => $question->id,
                    'option_text' => $mcq_option['answer'],
                    'is_correct' => $mcq_option['correct_or_not'] == "1" ? 1 : 0,
                    'question_explanation' => !empty($mcq_option['question_explanation']) ? $mcq_option['question_explanation'] : '',
                ]);
            }

            return res_data(
                $question->load('options'),
                'تم تعديل السؤال والخيارات بنجاح',
                200
            );
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function editParagraphQuestions(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:question_paragraphs,id',
                'paragraph_content' => 'nullable',
                'voice' => 'nullable',
                'description' => 'nullable',
            ]);

            $quesParagra =  QuestionParagraphsModel::find($data['id']);

            $quesParagra->update([
                'paragraph_content' => $data['paragraph_content'],
                'voice' => $data['voice'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            return res_data($quesParagra, 'تم تعديل الفقرة بنجاح', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->validator->errors()->first(), 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'حدث خطأ أثناء تعديل الفقرة', 500);
        }
    }


    public function deleteParagraphQuestions(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:question_paragraphs,id',
            ]);

            $paragraph = QuestionParagraphsModel::find($data['id']);

            // Get all questions associated with this paragraph
            $questions = AdminQuestionsModel::where('paragraph_id', $paragraph->id)->get();

            foreach ($questions as $question) {
                // Delete options for each question
                QuestionOptionsModel::where('question_id', $question->id)->delete();
                // Delete the question itself
                $question->delete();
            }

            // Finally, delete the paragraph
            $paragraph->delete();

            return res_data('تم حذف الفقرة والأسئلة المرتبطة بها بنجاح', 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->validator->errors()->first(), 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'حدث خطأ أثناء حذف الفقرة', 500);
        }
    }




    public function deleteQuestion(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:questions,id',
            ]);

            $question = AdminQuestionsModel::find($data['id']);

            QuestionOptionsModel::where('question_id', $question->id)->delete();
            $question->delete();

            return res_data('تم حذف السؤال بنجاح', 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->validator->errors()->first(), 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'حدث خطأ أثناء حذف السؤال', 500);
        }
    }

    public function makeAutoGenerateQuestions(Request $request)
    {
        try {
            $request->validate([
                'exam_section_id' => 'required|exists:exam_sections,id',
                'question_bank_skills_id' => 'required|exists:question_bank_skills,id',
                'questions_count' => 'nullable|integer|min:0',
                'paragraphs_count' => 'nullable|integer|min:0',
            ]);

            $sectionId = $request->input('exam_section_id');
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
                // Get question texts already used in this exam section (excluding paragraph questions)
                $usedQuestionTexts = AdminQuestionsModel::where('exam_section_id', $sectionId)
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
                    // Create exam question
                    $adminQuestion = AdminQuestionsModel::create([
                        'exam_section_id' => $sectionId,
                        'question_text' => $bankQuestion->question_text,
                        'question_type' => 'mcq',
                        'instructions' => $bankQuestion->instructions,
                        'paragraph_id' => null,
                    ]);

                    // Copy question options
                    foreach ($bankQuestion->options as $option) {
                        QuestionOptionsModel::create([
                            'question_id' => $adminQuestion->id,
                            'option_text' => $option->option_text,
                            'is_correct' => $option->is_correct ? '1' : '0',
                            'question_explanation' => '',
                        ]);
                    }

                    $copiedQuestions[] = $adminQuestion;
                }
            }

            // 2. Generate Paragraph questions if requested
            if ($paragraphsCount > 0) {
                // Get already used paragraph contents in this section
                $usedParagraphContents = QuestionParagraphsModel::where('exam_section_id', $sectionId)
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

                $selectedBankParagraphs = $availableBankParagraphs->random($paragraphsCount);

                foreach ($selectedBankParagraphs as $bankParagraph) {
                    // Create exam paragraph
                    $adminParagraph = QuestionParagraphsModel::create([
                        'exam_section_id' => $sectionId,
                        'paragraph_content' => $bankParagraph->paragraph_content,
                    ]);

                    // Copy questions for this paragraph
                    foreach ($bankParagraph->questions as $bankQuestion) {
                        $adminQuestion = AdminQuestionsModel::create([
                            'exam_section_id' => $sectionId,
                            'question_text' => $bankQuestion->question_text,
                            'question_type' => 'paragraph_mcq',
                            'instructions' => $bankQuestion->instructions,
                            'paragraph_id' => $adminParagraph->id,
                        ]);

                        // Copy options
                        foreach ($bankQuestion->options as $option) {
                            QuestionOptionsModel::create([
                                'question_id' => $adminQuestion->id,
                                'option_text' => $option->option_text,
                                'is_correct' => $option->is_correct ? '1' : '0',
                                'question_explanation' => '',
                            ]);
                        }
                    }

                    $copiedParagraphs[] = $adminParagraph;
                }
            }



            return res_data([
                'questions_count' => count($copiedQuestions),
                'paragraphs_count' => count($copiedParagraphs),
                'exam_section_id' => $sectionId,
            ], 'تم إنشاء الأسئلة والقطع بنجاح', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->errors(), 'Validation Error', 422);
        } catch (\Exception $e) {
            Log::error('makeAutoGenerateQuestions Error: ' . $e->getMessage());
            return res_data($e->getMessage(), 'error', 500);
        }
    }

    public function uploadAudio(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ], [
            'file.required' => 'ملف الصوت مطلوب',
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

                // Store the file in a specific directory for audio
                $path = $file->storeAs('uploads/audio', $fileName, 'public');

                // Generate the public URL
                $url = asset('storage/' . $path);

                return res_data([
                    'audio_url' => $url,
                    'audio_path' => $path,
                    'audio_name' => $fileName,
                ], 'تم رفع الملف الصوتي بنجاح', 200);
            }

            return res_data(null, 'لم يتم العثور على ملف مرفق', 400);
        } catch (\Exception $e) {
            Log::error('uploadAudio Error: ' . $e->getMessage());
            return res_data($e->getMessage(), 'حدث خطأ أثناء رفع ملف الصوت', 500);
        }
    }
}
