<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\QuestionsModel;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function get_questions(Request $request)
    {
        try {
            $data = $request->validate([
                'exam_section_id' => 'required|exists:exam_sections,id',
            ]);

            $questions = AdminQuestionsModel::where('exam_section_id', $data['exam_section_id'])
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

            return res_data([
                'questions' => $formattedQuestions,
                'questions_count' => count($formattedQuestions),
            ], 'تم جلب الأسئلة بنجاح', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessage = $e->validator->errors()->first();
            return res_data($errorMessage, 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'خطأ غير معروف';
            return res_data($errorMessage, 'حدث خطأ أثناء جلب الأسئلة', 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
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
