<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\GetFreeRoundExamRequest;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\ExamModel;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_free_round_exam(GetFreeRoundExamRequest $request)
    {
        $data = $request->validated();
        $assign_exam_round = AssignExamModel::where('lesson_or_round_id', $data['round_id'])->where('type', 'full_round')->get();
        $exam = ExamModel::whereIn('id', $assign_exam_round->pluck('exam_id'))->where('free', '1')->get();
        return res_data($exam, 'success', 200);
    }

    public function GetRoundExams(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);
        $assign_exam_round = AssignExamModel::where('lesson_or_round_id', $data['round_id'])->where('type', 'full_round')->get();
        $exam = ExamModel::whereIn('id', $assign_exam_round->pluck('exam_id'))->where('free', '0')->get();
        return res_data($exam, 'success', 200);
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
