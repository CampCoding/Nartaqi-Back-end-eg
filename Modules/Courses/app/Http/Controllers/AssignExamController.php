<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Http\Requests\AddExamPdfRequest;
use Modules\Courses\Http\Requests\AddExamVideoRequest;
use Modules\Courses\Http\Requests\EditExamPdfRequest;
use Modules\Courses\Http\Requests\EditExamVideoRequest;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\AdminExamSectionModel;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Models\QuestionAnswersModel;
use Modules\Courses\Models\QuestionOptionsModel;
use Modules\Courses\Models\QuestionParagraphsModel;

class AssignExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function add_exam_video(AddExamVideoRequest $request)
    {
        $data = $request->validated();
        $exam_video = ExamVideoModel::create($data);
        return res_data($exam_video, 'success', 200);
    }
    public function edit_exam_video(EditExamVideoRequest $request)
    {
        $data = $request->validated();
        $exam_video = ExamVideoModel::find($data['id']);
        $exam_video->update($data);
        return res_data($exam_video, 'success', 200);
    }
    public function delete_exam_video(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_videos,id',
        ]);
        $exam_video = ExamVideoModel::find($data['id']);
        $exam_video->delete();
        return res_data('success', 'success', 200);
    }


    public function add_exam_pdf(AddExamPdfRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('pdf_url')) {
            $data['pdf_url'] = $this->storeExamPdf($request->file('pdf_url'));
        }

        $exam_pdf = ExamPdfsModel::create($data);
        return res_data($exam_pdf, 'success', 200);
    }

    public function edit_exam_pdf(EditExamPdfRequest $request)
    {
        $data = $request->validated();
        $exam_pdf = ExamPdfsModel::findOrFail($data['id']);

        if ($request->hasFile('pdf_url')) {
            $newPath = $this->storeExamPdf($request->file('pdf_url'));

            if ($exam_pdf->pdf_url && Storage::disk('public')->exists($exam_pdf->pdf_url)) {
                Storage::disk('public')->delete($exam_pdf->pdf_url);
            }

            $data['pdf_url'] = $newPath;
        }

        $exam_pdf->update($data);
        return res_data($exam_pdf->fresh(), 'success', 200);
    }

    public function delete_exam_pdf(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_pdfs,id',
        ]);
        $exam_pdf = ExamPdfsModel::find($data['id']);
        $exam_pdf->delete();
        if ($exam_pdf->pdf_url && Storage::disk('public')->exists($exam_pdf->pdf_url)) {
            Storage::disk('public')->delete($exam_pdf->pdf_url);
        }
        return res_data('success', 'success', 200);
    }

    /**
     * Store the uploaded exam PDF and return the relative storage path.
     */
    private function storeExamPdf($file): string
    {
        return $file->store('exam_pdfs', 'public');
    }






    public function assign_intern_exam_round(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'lesson_or_round_id' => 'required'
        ]);

        // Directly assign the existing exam to the round/lesson without copying
        $assign_exam_round = AssignExamModel::create([
            'type' => $data['type'],
            'exam_id' => $data['exam_id'],
            'lesson_or_round_id' => $data['lesson_or_round_id']
        ]);

        return res_data($assign_exam_round, 'تم تعيين الامتحان بنجاح', 200);
    }



    public function editAssignShowDate(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:assign_exam_round,id',
            'show_date' => 'nullable',
        ]);
        $assign = AssignExamModel::find($data['id']);
        $assign->update([
            'show_date' => $data['show_date'],
        ]);
        return res_data($assign, 'success', 200);
    }




    public function assign_exam_round(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'lesson_or_round_id' => 'required',
            'show_date' => 'nullable'
        ]);

        // 1. Get the original exam
        $originalExam = AdminExamModel::findOrFail($data['exam_id']);

        // 2. Create a copy of the exam
        $newExam = $originalExam->replicate();
        $newExam->from_copy = '1';
        $newExam->save();

        // 3. Get all sections from the original exam
        $originalSections = AdminExamSectionModel::where('exam_id', $originalExam->id)->get();

        foreach ($originalSections as $originalSection) {
            // 4. Create a copy of each section
            $newSection = $originalSection->replicate();
            $newSection->exam_id = $newExam->id;
            $newSection->save();

            // Map to store old paragraph ID => new paragraph ID
            $paragraphIdMap = [];

            // Copy questions for this section
            $originalQuestions = AdminQuestionsModel::where('exam_section_id', $originalSection->id)->get();

            foreach ($originalQuestions as $originalQuestion) {
                // Map to store old option ID => new option ID
                $optionIdMap = [];

                // Create a copy of the question
                $newQuestion = $originalQuestion->replicate();
                $newQuestion->exam_section_id = $newSection->id;

                // Copy paragraph if this question references one and it hasn't been copied yet
                if ($originalQuestion->paragraph_id) {
                    // Check if we've already copied this paragraph
                    if (!isset($paragraphIdMap[$originalQuestion->paragraph_id])) {
                        // Get the original paragraph
                        $originalParagraph = QuestionParagraphsModel::find($originalQuestion->paragraph_id);

                        if ($originalParagraph) {
                            // Create a copy of the paragraph
                            $newParagraph = new QuestionParagraphsModel();
                            $newParagraph->exam_section_id = $newSection->id;
                            $newParagraph->paragraph_content = $originalParagraph->paragraph_content;
                            $newParagraph->save();

                            // Store mapping
                            $paragraphIdMap[$originalQuestion->paragraph_id] = $newParagraph->id;
                        }
                    }

                    // Update question's paragraph_id to the new copied paragraph
                    if (isset($paragraphIdMap[$originalQuestion->paragraph_id])) {
                        $newQuestion->paragraph_id = $paragraphIdMap[$originalQuestion->paragraph_id];
                    }
                }

                $newQuestion->save();

                // 7. Copy question options
                $originalOptions = QuestionOptionsModel::where('question_id', $originalQuestion->id)->get();
                foreach ($originalOptions as $originalOption) {
                    $newOption = $originalOption->replicate();
                    $newOption->question_id = $newQuestion->id;
                    $newOption->save();

                    // Store mapping for answer reference
                    $optionIdMap[$originalOption->id] = $newOption->id;
                }

                // 8. Copy question answer and update correct_option_id reference
                $originalAnswer = QuestionAnswersModel::where('question_id', $originalQuestion->id)->first();
                if ($originalAnswer) {
                    $newAnswer = $originalAnswer->replicate();
                    $newAnswer->question_id = $newQuestion->id;

                    // Update correct_option_id to point to the new option
                    if ($originalAnswer->correct_option_id && isset($optionIdMap[$originalAnswer->correct_option_id])) {
                        $newAnswer->correct_option_id = $optionIdMap[$originalAnswer->correct_option_id];
                    }

                    $newAnswer->save();
                }
            }
        }

        // 9. Calculate sort_number based on lesson_or_round_id and type
        $maxSort = AssignExamModel::where('lesson_or_round_id', $data['lesson_or_round_id'])
            ->where('type', $data['type'])
            ->max('sort_number');
        $sortNumber = $maxSort ? $maxSort + 1 : 1;

        // 10. Assign the new exam to the round/lesson
        $assign_exam_round = AssignExamModel::create([
            'type' => $data['type'],
            'exam_id' => $newExam->id,
            'lesson_or_round_id' => $data['lesson_or_round_id'],
            'show_date' => $data['show_date'],
            'sort_number' => $sortNumber
        ]);

        return res_data($assign_exam_round, 'تم نسخ الامتحان و تعيينه بنجاح', 200);
    }

    public function getExamInfo(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);
        $exam = AdminExamModel::findOrFail($data['exam_id']);
        return res_data($exam, 'success', 200);
    }
    public function index()
    {
        return view('courses::index');
    }


    public function create()
    {
        return view('courses::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('courses::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('courses::edit');
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
