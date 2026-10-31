<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
use Modules\Admins\Models\Admin;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AdminExamSectionModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\Rounds;
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
                ->orderBy('sort_order')
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

            // Soft delete: the paragraph's questions go to the trash (getDeletedQuestions) and can be
            // restored, so the paragraph row and the options are kept.
            $questions = AdminQuestionsModel::where('paragraph_id', $paragraph->id)->get();
            if ($questions->isEmpty()) {
                return res_data('القطعة دي محذوفة بالفعل', 'خطأ في التحقق من البيانات', 422);
            }

            $this->trashQuestions($questions, $request);

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
            if (!$question) {
                return res_data('السؤال ده محذوف بالفعل', 'خطأ في التحقق من البيانات', 422);
            }

            // Soft delete: the options are kept so the question can be restored as it was.
            $this->trashQuestions(collect([$question]), $request);

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
                    $copiedQuestions[] = $this->copyBankQuestionToSection($bankQuestion, (int) $sectionId);
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
                    $copiedParagraphs[] = $this->copyBankParagraphToSection($bankParagraph, (int) $sectionId);
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

    /**
     * Search the question bank within a skill so the admin can hand-pick questions (type=mcq)
     * or whole paragraphs (type=paragraph) to copy into an exam section.
     */
    public function searchBankQuestions(Request $request)
    {
        $data = $request->validate([
            'question_bank_skills_id' => 'required|exists:question_bank_skills,id',
            'type' => 'nullable|in:mcq,paragraph',
            'search' => 'nullable|string|max:255',
            'exam_section_id' => 'nullable|exists:exam_sections,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $type = $data['type'] ?? 'mcq';
        $perPage = (int) ($data['per_page'] ?? 20);
        $sectionId = $data['exam_section_id'] ?? null;
        $like = filled($data['search'] ?? null)
            ? '%' . addcslashes($data['search'], '\\%_') . '%'
            : null;

        if ($type === 'paragraph') {
            $results = QuestionBankParagraphModel::with('questions.options')
                ->where('question_bank_skills_id', $data['question_bank_skills_id'])
                ->when($like, fn ($q) => $q->where(function ($w) use ($like) {
                    $w->where('paragraph_content', 'like', $like)
                        ->orWhereHas('questions', fn ($qq) => $qq->where('question_text', 'like', $like));
                }))
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            $usedInSection = $sectionId
                ? array_flip(QuestionParagraphsModel::where('exam_section_id', $sectionId)
                    ->whereNotNull('paragraph_content')->pluck('paragraph_content')->all())
                : [];

            $results->getCollection()->each(function ($paragraph) use ($usedInSection) {
                $paragraph->already_in_section = isset($usedInSection[$paragraph->paragraph_content]);
            });

            return res_data($results, 'success', 200);
        }

        $results = QuestionsBankModel::with('options')
            ->where('question_bank_skills_id', $data['question_bank_skills_id'])
            ->where('type', 'mcq')
            ->when($like, fn ($q) => $q->where(function ($w) use ($like) {
                $w->where('question_text', 'like', $like)->orWhere('instructions', 'like', $like);
            }))
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $usedInSection = $sectionId
            ? array_flip(AdminQuestionsModel::where('exam_section_id', $sectionId)
                ->whereNull('paragraph_id')->whereNotNull('question_text')->pluck('question_text')->all())
            : [];

        $results->getCollection()->each(function ($question) use ($usedInSection) {
            $question->already_in_section = isset($usedInSection[$question->question_text]);
        });

        return res_data($results, 'success', 200);
    }

    /**
     * Copy hand-picked bank questions and/or whole bank paragraphs into an exam section, the same
     * way makeAutoGenerateQuestions copies them. The bank originals are left untouched.
     */
    public function addBankQuestionsToSection(Request $request)
    {
        $data = $request->validate([
            'exam_section_id' => 'required|exists:exam_sections,id',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|distinct|exists:questions_bank,id',
            'paragraph_ids' => 'nullable|array',
            'paragraph_ids.*' => 'integer|distinct|exists:question_bank_paragraphs,id',
        ]);

        $questionIds = collect($data['question_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        $paragraphIds = collect($data['paragraph_ids'] ?? [])->map(fn ($id) => (int) $id)->values();

        if ($questionIds->isEmpty() && $paragraphIds->isEmpty()) {
            return res_data('اختر سؤالاً أو قطعة واحدة على الأقل', 'خطأ في التحقق من البيانات', 422);
        }

        $bankQuestions = QuestionsBankModel::with('options')
            ->whereIn('id', $questionIds)
            ->where('type', 'mcq')
            ->get()
            ->sortBy(fn ($q) => $questionIds->search($q->id))
            ->values();

        // A paragraph's questions only make sense with their paragraph, so they're added via paragraph_ids.
        if ($bankQuestions->count() !== $questionIds->count()) {
            return res_data('بعض الأسئلة المختارة تابعة لقطعة؛ أضف القطعة كاملة من paragraph_ids', 'خطأ في التحقق من البيانات', 422);
        }

        $bankParagraphs = QuestionBankParagraphModel::with('questions.options')
            ->whereIn('id', $paragraphIds)
            ->get()
            ->sortBy(fn ($p) => $paragraphIds->search($p->id))
            ->values();

        $sectionId = (int) $data['exam_section_id'];

        [$newQuestions, $newParagraphs] = DB::transaction(function () use ($bankQuestions, $bankParagraphs, $sectionId) {
            $newQuestions = $bankQuestions->map(fn ($q) => $this->copyBankQuestionToSection($q, $sectionId));
            $newParagraphs = $bankParagraphs->map(fn ($p) => $this->copyBankParagraphToSection($p, $sectionId));

            return [$newQuestions, $newParagraphs];
        });

        return res_data([
            'exam_section_id' => $sectionId,
            'questions_count' => $newQuestions->count(),
            'paragraphs_count' => $newParagraphs->count(),
            'question_ids' => $newQuestions->pluck('id'),
            'paragraph_ids' => $newParagraphs->pluck('id'),
        ], 'تم إضافة الأسئلة للاختبار بنجاح', 201);
    }

    private function copyBankQuestionToSection($bankQuestion, int $sectionId, ?int $paragraphId = null): AdminQuestionsModel
    {
        $question = AdminQuestionsModel::create([
            'exam_section_id' => $sectionId,
            'question_text' => $bankQuestion->question_text,
            'question_type' => $paragraphId ? 'paragraph_mcq' : 'mcq',
            'instructions' => $bankQuestion->instructions,
            'paragraph_id' => $paragraphId,
        ]);

        foreach ($bankQuestion->options as $option) {
            QuestionOptionsModel::create([
                'question_id' => $question->id,
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct ? '1' : '0',
                'question_explanation' => '',
            ]);
        }

        return $question;
    }

    private function copyBankParagraphToSection($bankParagraph, int $sectionId): QuestionParagraphsModel
    {
        $paragraph = QuestionParagraphsModel::create([
            'exam_section_id' => $sectionId,
            'paragraph_content' => $bankParagraph->paragraph_content,
        ]);

        foreach ($bankParagraph->questions as $bankQuestion) {
            $this->copyBankQuestionToSection($bankQuestion, $sectionId, $paragraph->id);
        }

        return $paragraph;
    }

    /**
     * Reorder the questions of an exam section. `items` is the list in its new order, using the
     * same blocks get_questions returns: a standalone question is {type: question, id: question id}
     * and a paragraph is {type: paragraph, id: paragraph id}, optionally with question_ids to also
     * reorder the questions inside it. Anything left out keeps its current order after the listed ones.
     */
    public function sortQuestions(Request $request)
    {
        $data = $request->validate([
            'exam_section_id' => 'required|exists:exam_sections,id',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:question,paragraph',
            'items.*.id' => 'required|integer',
            'items.*.question_ids' => 'nullable|array',
            'items.*.question_ids.*' => 'integer',
        ]);

        $sectionId = (int) $data['exam_section_id'];

        $questions = AdminQuestionsModel::where('exam_section_id', $sectionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'question_type', 'paragraph_id', 'sort_order']);

        // Same grouping as get_questions: all paragraph_mcq questions of one paragraph are one block.
        $blocks = [];
        foreach ($questions as $question) {
            $key = $question->question_type === 'paragraph_mcq'
                ? 'paragraph:' . $question->paragraph_id
                : 'question:' . $question->id;
            $blocks[$key][] = $question;
        }

        $ordered = [];
        foreach ($data['items'] as $index => $item) {
            $key = $item['type'] . ':' . (int) $item['id'];
            $label = 'العنصر رقم ' . ($index + 1);

            if (!isset($blocks[$key])) {
                return res_data($label . ' غير موجود في هذا القسم', 'خطأ في التحقق من البيانات', 422);
            }
            if (isset($ordered[$key])) {
                return res_data($label . ' مكرر في الترتيب', 'خطأ في التحقق من البيانات', 422);
            }

            $block = $blocks[$key];

            if ($item['type'] === 'paragraph' && !empty($item['question_ids'])) {
                $positions = array_flip(array_map('intval', $item['question_ids']));
                $blockIds = array_map(fn ($q) => $q->id, $block);

                if (array_diff(array_keys($positions), $blockIds)) {
                    return res_data($label . ': بعض الأسئلة في question_ids مش تابعة للقطعة دي', 'خطأ في التحقق من البيانات', 422);
                }

                usort($block, fn ($a, $b) => ($positions[$a->id] ?? PHP_INT_MAX) <=> ($positions[$b->id] ?? PHP_INT_MAX));
            }

            $ordered[$key] = $block;
        }

        // Blocks the request didn't mention (e.g. added after the admin opened the page) go last.
        $ordered += $blocks;

        DB::transaction(function () use ($ordered) {
            $position = 0;
            foreach ($ordered as $block) {
                foreach ($block as $question) {
                    $position++;
                    if ((int) $question->sort_order !== $position) {
                        AdminQuestionsModel::where('id', $question->id)->update(['sort_order' => $position]);
                    }
                }
            }
        });

        return res_data([
            'exam_section_id' => $sectionId,
            'order' => collect(array_keys($ordered))->map(function ($key) {
                [$type, $id] = explode(':', $key, 2);
                return ['type' => $type, 'id' => (int) $id];
            })->values(),
        ], 'تم ترتيب الأسئلة بنجاح', 200);
    }

    /**
     * Trash of deleted exam questions. Same blocks as get_questions (a standalone question, or a
     * paragraph with its deleted questions), each with where it was deleted from: the exam section, the
     * exam and the rounds/lessons the exam is assigned to. Filters are optional.
     */
    public function getDeletedQuestions(Request $request)
    {
        $data = $request->validate([
            'exam_section_id' => 'nullable|exists:exam_sections,id',
            'exam_id' => 'nullable|exists:exams,id',
            'round_id' => 'nullable|exists:rounds,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = AdminQuestionsModel::onlyTrashed()
            ->when($data['exam_section_id'] ?? null, fn ($q, $id) => $q->where('exam_section_id', $id))
            ->when($data['exam_id'] ?? null, fn ($q, $id) => $q->whereIn(
                'exam_section_id',
                AdminExamSectionModel::where('exam_id', $id)->select('id')
            ))
            ->when($data['round_id'] ?? null, fn ($q, $id) => $q->whereIn(
                'exam_section_id',
                AdminExamSectionModel::whereIn('exam_id', $this->roundExamIds((int) $id))->select('id')
            ))
            ->when(filled($data['search'] ?? null), function ($q) use ($data) {
                $like = '%' . addcslashes($data['search'], '\\%_') . '%';
                $q->where('question_text', 'like', $like);
            })
            ->orderBy('deleted_at', 'desc')
            ->orderBy('id', 'desc');

        // Group into blocks first, then paginate the blocks, so a paragraph is never split across pages.
        $blocks = [];
        foreach ($query->get(['id', 'exam_section_id', 'question_type', 'paragraph_id', 'deleted_at']) as $question) {
            $key = $question->question_type === 'paragraph_mcq' && $question->paragraph_id
                ? 'paragraph:' . $question->paragraph_id
                : 'question:' . $question->id;
            $blocks[$key][] = $question->id;
        }

        $perPage = (int) ($data['per_page'] ?? 20);
        $page = (int) ($data['page'] ?? 1);
        $pageBlocks = array_slice($blocks, ($page - 1) * $perPage, $perPage, true);

        $questions = AdminQuestionsModel::onlyTrashed()
            ->with(['options', 'paragraph'])
            ->whereIn('id', collect($pageBlocks)->flatten()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $sections = AdminExamSectionModel::whereIn('id', $questions->pluck('exam_section_id')->unique())->get()->keyBy('id');
        $exams = AdminExamModel::whereIn('id', $sections->pluck('exam_id')->unique())->get(['id', 'title'])->keyBy('id');
        $usedIn = $this->examLocations($exams->keys()->all());
        $admins = Admin::whereIn('id', $questions->pluck('deleted_by')->filter()->unique())->get(['id', 'name'])->keyBy('id');

        $formatQuestion = fn ($q) => [
            'id' => $q->id,
            'question_text' => $q->question_text,
            'question_type' => $q->question_type,
            'instructions' => $q->instructions,
            'description' => $q->description,
            'paragraph_id' => $q->paragraph_id,
            'options' => $q->options,
            'deleted_at' => $q->deleted_at?->toDateTimeString(),
        ];

        $items = [];
        foreach ($pageBlocks as $key => $ids) {
            [$type, $id] = explode(':', $key, 2);
            $blockQuestions = collect($ids)->map(fn ($qid) => $questions[$qid] ?? null)->filter()->sortBy('sort_order')->values();
            if ($blockQuestions->isEmpty()) {
                continue;
            }

            $first = $blockQuestions->first();
            $section = $sections[$first->exam_section_id] ?? null;
            $exam = $section ? ($exams[$section->exam_id] ?? null) : null;
            $deletedAt = $blockQuestions->max('deleted_at');
            $deletedBy = $blockQuestions->sortByDesc('deleted_at')->first()->deleted_by;

            $item = [
                'type' => $type,
                'id' => (int) $id,
                'deleted_at' => $deletedAt?->toDateTimeString(),
                'deleted_by' => $deletedBy && isset($admins[$deletedBy]) ? ['id' => $admins[$deletedBy]->id, 'name' => $admins[$deletedBy]->name] : null,
                'deleted_from' => [
                    'exam_section' => $section ? ['id' => $section->id, 'title' => $section->title] : null,
                    'exam' => $exam ? ['id' => $exam->id, 'title' => $exam->title] : null,
                    'used_in' => $exam ? ($usedIn[$exam->id] ?? []) : [],
                ],
            ];

            if ($type === 'paragraph') {
                $paragraph = $first->paragraph;
                $item['paragraph'] = $paragraph ? [
                    'id' => $paragraph->id,
                    'paragraph_content' => $paragraph->paragraph_content,
                    'voice' => $paragraph->voice,
                    'description' => $paragraph->description,
                ] : null;
                $item['questions'] = $blockQuestions->map($formatQuestion)->all();
                // How many of this paragraph's questions are still in the exam (0 = the whole paragraph was deleted).
                $item['active_questions_count'] = AdminQuestionsModel::where('paragraph_id', $id)->count();
            } else {
                $item['question'] = $formatQuestion($first);
            }

            $items[] = $item;
        }

        return res_data([
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => count($blocks),
            'last_page' => max(1, (int) ceil(count($blocks) / $perPage)),
        ], 'success', 200);
    }

    /**
     * Put trashed questions back where they were (same exam section, same position).
     * items: {type: question, id: question id} or {type: paragraph, id: paragraph id} (restores all of
     * that paragraph's deleted questions), the same ids getDeletedQuestions returns.
     */
    public function restoreQuestions(Request $request)
    {
        [$questions, $error] = $this->trashedQuestionsFor($request);
        if ($error) {
            return res_data($error, 'خطأ في التحقق من البيانات', 422);
        }

        DB::transaction(function () use ($questions) {
            AdminQuestionsModel::onlyTrashed()
                ->whereIn('id', $questions->pluck('id'))
                ->update(['deleted_at' => null, 'deleted_by' => null]);
        });

        return res_data([
            'restored_question_ids' => $questions->pluck('id')->values(),
            'exam_section_ids' => $questions->pluck('exam_section_id')->unique()->values(),
        ], 'تم استرجاع الأسئلة بنجاح', 200);
    }

    /**
     * Permanently delete trashed questions (with their options and answers). A paragraph left with no
     * questions at all is removed too. Same items as restoreQuestions.
     */
    public function forceDeleteQuestions(Request $request)
    {
        [$questions, $error] = $this->trashedQuestionsFor($request);
        if ($error) {
            return res_data($error, 'خطأ في التحقق من البيانات', 422);
        }

        $ids = $questions->pluck('id');
        $paragraphIds = $questions->pluck('paragraph_id')->filter()->unique();

        DB::transaction(function () use ($ids, $paragraphIds) {
            QuestionOptionsModel::whereIn('question_id', $ids)->delete();
            QuestionAnswersModel::whereIn('question_id', $ids)->delete();
            AdminQuestionsModel::onlyTrashed()->whereIn('id', $ids)->forceDelete();

            $stillUsed = AdminQuestionsModel::withTrashed()->whereIn('paragraph_id', $paragraphIds)->pluck('paragraph_id')->unique();
            QuestionParagraphsModel::whereIn('id', $paragraphIds->diff($stillUsed))->delete();
        });

        return res_data(['deleted_question_ids' => $ids->values()], 'تم حذف الأسئلة نهائيًا', 200);
    }

    private function trashQuestions($questions, Request $request): void
    {
        $adminId = optional($request->user())->id;

        DB::transaction(function () use ($questions, $adminId) {
            AdminQuestionsModel::whereIn('id', $questions->pluck('id'))->update(['deleted_by' => $adminId]);
            AdminQuestionsModel::whereIn('id', $questions->pluck('id'))->delete();
        });
    }

    /**
     * Resolve restore/forceDelete items to trashed questions. Returns [questions, error message|null].
     */
    private function trashedQuestionsFor(Request $request): array
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:question,paragraph',
            'items.*.id' => 'required|integer',
        ]);

        $questions = collect();
        foreach ($data['items'] as $index => $item) {
            $found = AdminQuestionsModel::onlyTrashed()
                ->when(
                    $item['type'] === 'paragraph',
                    fn ($q) => $q->where('paragraph_id', $item['id']),
                    fn ($q) => $q->where('id', $item['id'])
                )
                ->get();

            if ($found->isEmpty()) {
                return [null, 'العنصر رقم ' . ($index + 1) . ' مش موجود في المحذوفات'];
            }

            $questions = $questions->merge($found);
        }

        return [$questions->unique('id')->values(), null];
    }

    /**
     * Exam ids used by a round: its whole-round exams plus the exams of its lessons.
     */
    private function roundExamIds(int $roundId): array
    {
        $lessonIds = LessonsModel::whereIn('round_content_id', RoundContetModel::where('round_id', $roundId)->select('id'))->pluck('id');

        return AssignExamModel::where(function ($q) use ($roundId, $lessonIds) {
            $q->where(fn ($w) => $w->where('type', 'full_round')->where('lesson_or_round_id', $roundId))
                ->orWhere(fn ($w) => $w->where('type', 'lesson')->whereIn('lesson_or_round_id', $lessonIds));
        })->pluck('exam_id')->unique()->values()->all();
    }

    /**
     * exam_id => where the exam is assigned: [{type: full_round, round}, {type: lesson, lesson, round}].
     * An exam with no assignment (e.g. a bank exam) gets an empty list.
     */
    private function examLocations(array $examIds): array
    {
        if (empty($examIds)) {
            return [];
        }

        $assignments = AssignExamModel::whereIn('exam_id', $examIds)->get();
        $lessons = LessonsModel::with('round_content:id,round_id,title')
            ->whereIn('id', $assignments->where('type', 'lesson')->pluck('lesson_or_round_id'))
            ->get(['id', 'title', 'round_content_id'])
            ->keyBy('id');
        $roundIds = $assignments->where('type', 'full_round')->pluck('lesson_or_round_id')
            ->merge($lessons->pluck('round_content.round_id'))
            ->filter()->unique();
        $rounds = Rounds::whereIn('id', $roundIds)->get(['id', 'name'])->keyBy('id');

        $result = [];
        foreach ($assignments as $assignment) {
            if ($assignment->type === 'lesson') {
                $lesson = $lessons[$assignment->lesson_or_round_id] ?? null;
                $round = $lesson && $lesson->round_content ? ($rounds[$lesson->round_content->round_id] ?? null) : null;
                $result[$assignment->exam_id][] = [
                    'type' => 'lesson',
                    'lesson' => $lesson ? ['id' => $lesson->id, 'title' => $lesson->title] : null,
                    'round' => $round ? ['id' => $round->id, 'name' => $round->name] : null,
                ];
            } else {
                $round = $rounds[$assignment->lesson_or_round_id] ?? null;
                $result[$assignment->exam_id][] = [
                    'type' => 'full_round',
                    'lesson' => null,
                    'round' => $round ? ['id' => $round->id, 'name' => $round->name] : null,
                ];
            }
        }

        return $result;
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
