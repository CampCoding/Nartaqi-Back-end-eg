<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Http\Requests\StoreExamRequest;
use Modules\Courses\Http\Requests\EditExamRequest;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Services\ExamCopyService;

class AdminExamController extends Controller
{
    public function __construct(private ExamCopyService $examCopyService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function GetAllExamsRoundByRoundId(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);
        $assign_exam_round = AssignExamModel::where('lesson_or_round_id', $data['round_id'])->where('type', 'full_round')->get();
        $exams = AdminExamModel::whereIn('id', $assign_exam_round->pluck('exam_id'))->get();
        return res_data($exams, 'تم جلب الامتحانات بنجاح', 200);
    }
    public function GetAllExamsLessonByLessonId(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
        ]);
        $assign_exam_lesson = AssignExamModel::where('lesson_or_round_id', $data['lesson_id'])->where('type', 'lesson')->get();
        $exams = AdminExamModel::whereIn('id', $assign_exam_lesson->pluck('exam_id'))->get();
        return res_data($exams, 'تم جلب الامتحانات بنجاح', 200);
    }
    public function store_exam(StoreExamRequest $request)
    {
        $data = $request->validated();
        $data['success_percentage'] = $data['success_percentage'] ?? 0;
        try {
            $exam = AdminExamModel::create($data);
            if ($exam) {
                return res_data($exam, 'تم انشاء الامتحان بنجاح', 201);
            }
            return res_data(null, 'فشل إنشاء الامتحان', 400);
        } catch (\Exception $e) {
            return res_data(null, 'حدث خطأ أثناء إنشاء الامتحان: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Create a new exam AND assign it to a round/lesson in one call, instead
     * of store_exam + assign_intern_exam_round as two separate requests.
     */
    public function store_exam_in_round(StoreExamRequest $request)
    {
        $assignData = $request->validate([
            'assign_type' => 'required|in:full_round,lesson',
            'lesson_or_round_id' => 'required',
            'show_date' => 'nullable',
        ]);

        $data = $request->validated();
        $data['success_percentage'] = $data['success_percentage'] ?? 0;

        $result = DB::transaction(function () use ($data, $assignData) {
            $exam = AdminExamModel::create($data);

            $assignment = AssignExamModel::create([
                'type' => $assignData['assign_type'],
                'exam_id' => $exam->id,
                'lesson_or_round_id' => $assignData['lesson_or_round_id'],
                'show_date' => $assignData['show_date'] ?? null,
            ]);

            return [$exam, $assignment];
        });

        [$exam, $assignment] = $result;

        return res_data([
            'exam' => $exam,
            'assignment' => $assignment,
        ], 'تم إنشاء الامتحان وربطه بالدورة بنجاح', 201);
    }

    public function edit_exam(EditExamRequest $request)
    {
        $data = $request->validated();
        $exam = AdminExamModel::find($data['id']);
        if (! $exam) {
            return res_data(null, 'الامتان غير موجود', 404);
        }
        $exam->update($data);
        return res_data($exam, 'تم تعديل الامتحان بنجاح', 200);
    }

    public function delete_exam(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exams,id',
        ]);
        $exam = AdminExamModel::find($data['id']);
        $exam->delete();
        AssignExamModel::where('exam_id', $data['id'])->delete();
        return res_data('تم حذف الامتحان بنجاح', 'success', 200);
    }

    /**
     * Copy an existing exam (sections, paragraphs, questions, options, answers)
     * into a brand new, independent exam instead of building one from scratch.
     */
    public function copy_exam(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $newExam = DB::transaction(function () use ($data) {
            $examIdMap = [];
            return $this->examCopyService->copyExamWithQuestions((int) $data['exam_id'], $examIdMap);
        });

        if (!$newExam) {
            return res_data(null, 'فشل نسخ الامتحان', 400);
        }

        $newExam->load('exam_sections.questions.options');

        return res_data($newExam, 'تم نسخ الامتحان بنجاح', 201);
    }

    public function get_exam_all_data_by_id(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exams,id',
        ]);
        $exam = AdminExamModel::find($data['id']);

        // Get lessons assigned to this exam
        $assignExams = AssignExamModel::where('exam_id', $exam->id)
            ->where('type', 'lesson')
            ->pluck('lesson_or_round_id');

        $exam_pdfs = ExamPdfsModel::whereIn('lesson_id', $assignExams)->get();
        $exam_videos = ExamVideoModel::whereIn('lesson_id', $assignExams)->get();

        return res_data(['exam' => $exam, 'exam_pdfs' => $exam_pdfs, 'exam_videos' => $exam_videos], 'success', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
