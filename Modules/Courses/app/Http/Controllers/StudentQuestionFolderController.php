<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\StudentQuestionFolderModel;
use Modules\Courses\Models\StudentQuestionFolderItemModel;
use Modules\Courses\Models\StudentFolderExamModel;
use Modules\Courses\Models\StudentFolderExamQuestionModel;
use Modules\Courses\Models\StudentFolderExamAnswerModel;
use Modules\Courses\Models\QuestionOptionsModel;

class StudentQuestionFolderController extends Controller
{
    /**
     * List the authenticated student's folders with question/exam counts.
     */
    public function index(Request $request)
    {
        $studentId = $request->user()->id;

        $folders = StudentQuestionFolderModel::where('student_id', $studentId)
            ->withCount(['items as questions_count', 'exams as exams_count'])
            ->orderBy('id', 'desc')
            ->get();

        return res_data($folders, 'success', 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $folder = StudentQuestionFolderModel::create([
            'student_id' => $request->user()->id,
            'name' => $data['name'],
        ]);

        return res_data($folder, 'success', 201);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $folder = StudentQuestionFolderModel::where('student_id', $request->user()->id)
            ->where('id', $data['id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        $folder->update(['name' => $data['name']]);

        return res_data($folder, 'success', 200);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $folder = StudentQuestionFolderModel::where('student_id', $request->user()->id)
            ->where('id', $data['id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        // Cascades to items, and to its exams (+ their questions/answers via FK cascade).
        $folder->delete();

        return res_data('تم حذف الفولدر بنجاح', 'success', 200);
    }

    /**
     * Folder details + saved questions.
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $studentId = $request->user()->id;

        $folder = StudentQuestionFolderModel::where('student_id', $studentId)
            ->where('id', $data['id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        $questionIds = StudentQuestionFolderItemModel::where('folder_id', $folder->id)
            ->orderBy('id', 'desc')
            ->pluck('question_id');

        $questions = AdminQuestionsModel::whereIn('id', $questionIds)
            ->with(['options', 'paragraph'])
            ->get()
            ->sortBy(fn ($q) => $questionIds->search($q->id))
            ->values();

        return res_data([
            'folder' => $folder,
            'questions_count' => $questions->count(),
            'questions' => $this->formatQuestions($questions),
        ], 'success', 200);
    }

    public function addQuestion(Request $request)
    {
        $data = $request->validate([
            'folder_id' => 'required|integer',
            'question_id' => 'required|integer|exists:questions,id',
        ], [
            'question_id.exists' => 'السؤال غير موجود',
        ]);

        $folder = StudentQuestionFolderModel::where('student_id', $request->user()->id)
            ->where('id', $data['folder_id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }
        $exists = StudentQuestionFolderItemModel::where('folder_id', $folder->id)
            ->where('question_id', $data['question_id'])
            ->exists();

        if ($exists) {
            return res_data('السؤال محفوظ بالفعل في هذا الفولدر', 'error', 422);
        }

        StudentQuestionFolderItemModel::create([
            'folder_id' => $folder->id,
            'question_id' => $data['question_id'],
        ]);

        return res_data('تم حفظ السؤال في الفولدر', 'success', 201);
    }

    public function removeQuestion(Request $request)
    {
        $data = $request->validate([
            'folder_id' => 'required|integer',
            'question_id' => 'required|integer',
        ]);

        $folder = StudentQuestionFolderModel::where('student_id', $request->user()->id)
            ->where('id', $data['folder_id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        // Only removes it from this folder; past folder exams keep their fixed question list.
        StudentQuestionFolderItemModel::where('folder_id', $folder->id)
            ->where('question_id', $data['question_id'])
            ->delete();

        return res_data('تم حذف السؤال من الفولدر', 'success', 200);
    }

    /**
     * Which of the authenticated student's folders already contain this question.
     */
    public function questionFolders(Request $request)
    {
        $data = $request->validate([
            'question_id' => 'required|integer',
        ]);

        $studentId = $request->user()->id;

        $savedFolderIds = StudentQuestionFolderItemModel::where('question_id', $data['question_id'])
            ->whereIn('folder_id', StudentQuestionFolderModel::where('student_id', $studentId)->pluck('id'))
            ->pluck('folder_id')
            ->toArray();

        $folders = StudentQuestionFolderModel::where('student_id', $studentId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($folder) use ($savedFolderIds) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'saved' => in_array($folder->id, $savedFolderIds),
                ];
            });

        return res_data($folders, 'success', 200);
    }

    /**
     * Generate a random exam from a folder's questions.
     */
    public function generateExam(Request $request)
    {
        $data = $request->validate([
            'folder_id' => 'required|integer',
            'question_count' => 'required',
            'mode' => 'required|in:default,tiger',
            'title' => 'nullable|string|max:255',
        ]);

        $studentId = $request->user()->id;

        $folder = StudentQuestionFolderModel::where('student_id', $studentId)
            ->where('id', $data['folder_id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        $availableQuestionIds = StudentQuestionFolderItemModel::where('folder_id', $folder->id)
            ->pluck('question_id');

        $availableCount = $availableQuestionIds->count();

        if ($availableCount === 0) {
            return res_data('الفولدر لا يحتوي على أسئلة', 'error', 422);
        }

        if ($data['question_count'] === 'all') {
            $requestedCount = $availableCount;
        } else {
            if (!is_numeric($data['question_count']) || (int) $data['question_count'] < 1) {
                return res_data('عدد الأسئلة غير صحيح', 'error', 422);
            }
            $requestedCount = (int) $data['question_count'];
        }

        if ($requestedCount > $availableCount) {
            return res_data([
                'requested' => $requestedCount,
                'available' => $availableCount,
            ], 'عدد الأسئلة المطلوب أكبر من عدد الأسئلة المتاحة في الفولدر', 400);
        }

        $selectedQuestionIds = $availableQuestionIds->shuffle()->take($requestedCount)->values();

        $folderExam = DB::transaction(function () use ($studentId, $folder, $data, $requestedCount, $selectedQuestionIds) {
            $folderExam = StudentFolderExamModel::create([
                'student_id' => $studentId,
                'folder_id' => $folder->id,
                'title' => $data['title'] ?? ('اختبار - ' . $folder->name),
                'mode' => $data['mode'],
                'question_count' => $requestedCount,
                'status' => 'created',
            ]);

            foreach ($selectedQuestionIds as $index => $questionId) {
                StudentFolderExamQuestionModel::create([
                    'folder_exam_id' => $folderExam->id,
                    'question_id' => $questionId,
                    'sort_order' => $index,
                ]);
            }

            return $folderExam;
        });

        return res_data($folderExam, 'success', 201);
    }

    /**
     * Exam history for a folder.
     */
    public function examHistory(Request $request)
    {
        $data = $request->validate([
            'folder_id' => 'required|integer',
        ]);

        $studentId = $request->user()->id;

        $folder = StudentQuestionFolderModel::where('student_id', $studentId)
            ->where('id', $data['folder_id'])
            ->first();

        if (!$folder) {
            return res_data('الفولدر غير موجود', 'error', 404);
        }

        $exams = StudentFolderExamModel::where('folder_id', $folder->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'mode' => $exam->mode,
                    'question_count' => $exam->question_count,
                    'score' => $exam->score !== null ? "{$exam->score}/{$exam->question_count}" : null,
                    'percentage' => $exam->percentage,
                    'status' => $exam->status,
                    'started_at' => $exam->started_at,
                    'finished_at' => $exam->finished_at,
                    'created_at' => $exam->created_at,
                ];
            });

        return res_data($exams, 'success', 200);
    }

    /**
     * Full exam-taking payload for a folder exam, shaped like the mock exam engine
     * (one section, questions[] with paragraph groups embedded) so the existing
     * frontend exam engine can render it unmodified.
     */
    public function showFolderExam(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $studentId = $request->user()->id;

        $folderExam = StudentFolderExamModel::where('student_id', $studentId)
            ->where('id', $data['id'])
            ->first();

        if (!$folderExam) {
            return res_data('الاختبار غير موجود', 'error', 404);
        }

        if (!$folderExam->started_at) {
            $folderExam->update(['started_at' => now(), 'status' => 'in_progress']);
        }

        $orderedQuestionIds = StudentFolderExamQuestionModel::where('folder_exam_id', $folderExam->id)
            ->orderBy('sort_order')
            ->pluck('question_id');

        $questions = AdminQuestionsModel::whereIn('id', $orderedQuestionIds)
            ->with(['options', 'paragraph'])
            ->get()
            ->sortBy(fn ($q) => $orderedQuestionIds->search($q->id))
            ->values();

        $answers = StudentFolderExamAnswerModel::where('folder_exam_id', $folderExam->id)
            ->get()
            ->keyBy('question_id');

        return res_data([
            'exam_info' => [
                'id' => $folderExam->id,
                'title' => $folderExam->title,
                'mode' => $folderExam->mode,
                'question_count' => $folderExam->question_count,
                'status' => $folderExam->status,
            ],
            'sections' => [
                [
                    'id' => $folderExam->id,
                    'title' => $folderExam->title,
                    'questions' => $this->formatQuestions($questions, $answers),
                ],
            ],
        ], 'success', 200);
    }

    public function storeAnswers(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.selected_option_id' => 'nullable|integer|exists:question_options,id',
            'answers.*.student_answer' => 'nullable|string',
        ]);

        $studentId = $request->user()->id;

        $folderExam = StudentFolderExamModel::where('student_id', $studentId)
            ->where('id', $data['id'])
            ->first();

        if (!$folderExam) {
            return res_data('الاختبار غير موجود', 'error', 404);
        }

        if ($folderExam->status === 'completed') {
            return res_data('لا يمكن تعديل الإجابات بعد إنهاء الاختبار', 'error', 422);
        }

        $validQuestionIds = StudentFolderExamQuestionModel::where('folder_exam_id', $folderExam->id)
            ->pluck('question_id')
            ->toArray();

        DB::transaction(function () use ($data, $folderExam, $validQuestionIds) {
            foreach ($data['answers'] as $answer) {
                if (!in_array($answer['question_id'], $validQuestionIds)) {
                    continue;
                }

                $isCorrect = false;
                if (!empty($answer['selected_option_id'])) {
                    $correctOption = QuestionOptionsModel::where('question_id', $answer['question_id'])
                        ->where('is_correct', 1)
                        ->first();
                    $isCorrect = $correctOption && (int) $correctOption->id === (int) $answer['selected_option_id'];
                }

                StudentFolderExamAnswerModel::updateOrCreate(
                    ['folder_exam_id' => $folderExam->id, 'question_id' => $answer['question_id']],
                    [
                        'selected_option_id' => $answer['selected_option_id'] ?? null,
                        'student_answer' => $answer['student_answer'] ?? null,
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        });

        return res_data('تم حفظ الإجابات', 'success', 200);
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $studentId = $request->user()->id;

        $folderExam = StudentFolderExamModel::where('student_id', $studentId)
            ->where('id', $data['id'])
            ->first();

        if (!$folderExam) {
            return res_data('الاختبار غير موجود', 'error', 404);
        }

        if ($folderExam->status === 'completed') {
            return res_data($folderExam, 'success', 200);
        }

        $score = StudentFolderExamAnswerModel::where('folder_exam_id', $folderExam->id)
            ->where('is_correct', 1)
            ->count();

        $percentage = $folderExam->question_count > 0
            ? (int) round(($score / $folderExam->question_count) * 100)
            : 0;

        $folderExam->update([
            'score' => $score,
            'percentage' => $percentage,
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        return res_data($folderExam, 'success', 200);
    }

    /**
     * Shared formatter: groups paragraph_mcq questions under their paragraph
     * (same convention as ExamSectionsController::get_mock_exam_sectionsWithQuestions).
     */
    private function formatQuestions($questions, $answers = null)
    {
        $formatted = [];
        $processedParagraphIds = [];

        foreach ($questions as $question) {
            if ($question->question_type === 'paragraph_mcq' && $question->paragraph_id !== null) {
                $pId = $question->paragraph_id;
                if (in_array($pId, $processedParagraphIds)) {
                    continue;
                }
                $processedParagraphIds[] = $pId;

                $paragraphQuestions = $questions->where('paragraph_id', $pId);
                $paragraphModel = $question->paragraph;

                $formatted[] = [
                    'id' => $paragraphModel ? $paragraphModel->id : $pId,
                    'question_type' => 'paragraph',
                    'paragraph' => $paragraphModel ? [
                        'id' => $paragraphModel->id,
                        'paragraph_content' => $paragraphModel->paragraph_content,
                        'voice' => $paragraphModel->voice,
                        'description' => $paragraphModel->description,
                    ] : null,
                    'questions' => $paragraphQuestions->map(function ($q) use ($answers) {
                        return $this->formatSingleQuestion($q, $answers);
                    })->values()->toArray(),
                ];
            } else {
                $formatted[] = $this->formatSingleQuestion($question, $answers);
            }
        }

        return $formatted;
    }

    private function formatSingleQuestion($question, $answers = null)
    {
        $answer = $answers ? $answers->get($question->id) : null;

        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'instructions' => $question->instructions,
            'description' => $question->question_type === 'paragraph_mcq' ? null : $question->description,
            'paragraph_id' => $question->paragraph_id,
            'options' => $question->options,
            'selected_option_id' => $answer->selected_option_id ?? null,
            'student_answer' => $answer->student_answer ?? null,
            'is_correct' => $answer->is_correct ?? null,
            'is_solved' => $answer ? true : false,
        ];
    }
}
