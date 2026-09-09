<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\EditExamSectionRequest;
use Modules\Courses\Http\Requests\StoreExamSectionRequest;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AdminExamSectionModel;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamVideoModel;

class AdminExamSectionController extends Controller
{


    public function GetAllExamSectionsByExamId(Request $request)
    {
        try {
            $data = $request->validate([
                'exam_id' => 'required|exists:exams,id',
            ]);

            $sections = AdminExamSectionModel::where('exam_id', $data['exam_id'])->get();

            $formattedSections = $sections->map(function ($section) {
                $questions = AdminQuestionsModel::where('exam_section_id', $section->id)
                    ->with(['options', 'answer', 'paragraph'])
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
                                'created_at' => $paragraphModel->created_at,
                                'updated_at' => $paragraphModel->updated_at,
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
                                    'answer' => $q->answer,
                                    'paragraph' => $q->paragraph,
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
                            'answer' => $question->answer,
                            'paragraph' => null,
                        ];
                    }
                }

                return array_merge($section->toArray(), [
                    'questions' => $formattedQuestions,
                    'questions_count' => count($formattedQuestions),
                ]);
            });

            return res_data($formattedSections, 'تم جلب الأقسام بنجاح', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessage = $e->validator->errors()->first();
            return res_data($errorMessage, 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'خطأ غير معروف';
            return res_data($errorMessage, 'حدث خطأ أثناء جلب الأقسام', 500);
        }
    }
    public function store_exam_section(StoreExamSectionRequest $request)
    {
        try {
            $data = $request->validated();
            $section = AdminExamSectionModel::create($data);
            if ($section) {
                $this->recalculateExamTotalTime($data['exam_id']);
            }
            return res_data(['section' => $section], 'success', 200);
        } catch (\Exception $e) {
            return res_data(['section' => null, 'error' => $e->getMessage()], 'حدث خطأ أثناء إنشاء القسم', 500);
        }
    }
    public function edit_exam_section(EditExamSectionRequest $request)
    {
        $data = $request->validated();
        $section = AdminExamSectionModel::find($data['id']);
        $section->update($data);
        $this->recalculateExamTotalTime($section->exam_id);
        return res_data($section, 'success', 200);
    }
    public function delete_exam_section(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|exists:exam_sections,id',
                'type' => 'required|in:mock,intern',
            ]);

            $section = AdminExamSectionModel::find($data['id']);

            if (!$section) {
                return res_data(['error' => 'القسم غير موجود'], 'خطأ', 404);
            }

            $examId = $section->exam_id;
            $section->delete();
            $this->recalculateExamTotalTime($examId);

            return res_data('تم حذف القسم بنجاح', 'success', 200);
        } catch (\Exception $e) {
            return res_data(['error' => $e->getMessage()], 'حدث خطأ أثناء حذف القسم', 500);
        }
    }

    /**
     * Exam total time = sum of every section's time_if_free (HH:MM:SS each).
     */
    private function recalculateExamTotalTime(int $examId): void
    {
        $exam = AdminExamModel::find($examId);
        if (!$exam) {
            return;
        }

        $totalMinutes = AdminExamSectionModel::where('exam_id', $examId)
            ->pluck('time_if_free')
            ->sum(function ($time) {
                if (!$time) {
                    return 0;
                }
                $parts = explode(':', $time);
                $hours = (int) ($parts[0] ?? 0);
                $minutes = (int) ($parts[1] ?? 0);
                return ($hours * 60) + $minutes;
            });

        $exam->time = sprintf('%02d:%02d:00', intdiv($totalMinutes, 60), $totalMinutes % 60);
        $exam->save();
    }

    public function getAllExams(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1',
            'exam_label_id' => 'nullable|exists:exam_labels,id',
        ]);

        $perPage = (int) $request->get('per_page');
        $examLabelId = $request->get('exam_label_id');

        // Get all exams that are NOT assigned as lesson in AssignExamModel
        $assignedAsLessonExamIds = AssignExamModel::where('type', 'lesson')
            ->pluck('exam_id')
            ->unique()
            ->toArray();

        $exams = AdminExamModel::withCount('questions')
            ->where('from_copy', '0')
            ->when(!empty($assignedAsLessonExamIds), function ($query) use ($assignedAsLessonExamIds) {
                $query->whereNotIn('id', $assignedAsLessonExamIds);
            })
            ->when($examLabelId, function ($query) use ($examLabelId) {
                $query->where('exam_label_id', $examLabelId);
            })
            ->orderBy('created_at', 'desc') // Get latest exams first
            ->paginate($perPage);

        return res_data($exams, 'success', 200);
    }
    public function getAllExamsByRoundId(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $assignments = AssignExamModel::where('type', 'full_round')
            ->where('lesson_or_round_id', $data['round_id'])
            ->orderBy('sort_number', 'asc')
            ->get();

        $assignedAsRoundExamIds = $assignments->pluck('exam_id')->unique()->toArray();

        $exams = AdminExamModel::withCount('questions')
            ->whereIn('id', $assignedAsRoundExamIds)
            ->get();

        $examVideos = ExamVideoModel::whereIn('lesson_id', $assignedAsRoundExamIds)->where('for_type', 'exam')->get()->groupBy('lesson_id');
        $examPdfs = ExamPdfsModel::whereIn('lesson_id', $assignedAsRoundExamIds)->where('for_type', 'exam')->get()->groupBy('lesson_id');

        $assignmentMap = $assignments->keyBy('exam_id');

        $exams->transform(function ($exam) use ($assignmentMap, $examVideos, $examPdfs) {
            if (isset($assignmentMap[$exam->id])) {
                $assignment = $assignmentMap[$exam->id];
                $exam->assign_data = [
                    'id' => $assignment->id,
                    'show_date' => $assignment->show_date,
                    'sort_number' => $assignment->sort_number,
                ];
            }

            $exam->videos = isset($examVideos[$exam->id]) ? $examVideos[$exam->id]->values() : [];
            $exam->pdfs = isset($examPdfs[$exam->id]) ? $examPdfs[$exam->id]->values() : [];

            return $exam;
        });

        // Sort exams by sort_number from assignments
        $sortedExams = $exams->sortBy(function ($exam) use ($assignmentMap) {
            return $assignmentMap[$exam->id]->sort_number ?? 0;
        })->values();

        return res_data($sortedExams, 'success', 200);
    }









    public function GetStudentScoresByExamId(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $studentScores = StudentScoreModel::where('exam_id', $data['exam_id'])
            ->with('student')
            ->get();

        // Group scores by student
        $groupedByStudent = $studentScores->groupBy('student_id');

        $students = $groupedByStudent->map(function ($scores, $studentId) {
            $student = $scores->first()->student;
            $timesScored = $scores->map(function ($score) {
                return [
                    'id' => $score->id,
                    'score' => $score->score,
                    'created_at' => $score->created_at,
                ];
            })->values();

            return [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'gender' => $student->gender,
                'image' => $student->image,
                'times_scored' => $timesScored,
            ];
        })->values();

        return res_data($students, 'success', 200);
    }

    public function sortExamRound(Request $request)
    {
        $data = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.id' => 'required|exists:assign_exam_round,id',
            'assignments.*.sort_number' => 'required|numeric',
        ]);

        foreach ($data['assignments'] as $assignmentData) {
            AssignExamModel::where('id', $assignmentData['id'])->update(['sort_number' => $assignmentData['sort_number']]);
        }

        return res_data('تم ترتيب الامتحانات بنجاح', 'success', 200);
    }
}
