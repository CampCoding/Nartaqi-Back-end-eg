<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\DeleteLessonRequest;
use Modules\Courses\Http\Requests\EditLessonRequest;
use Modules\Courses\Http\Requests\StoreLessonRequest;
use Modules\Courses\Models\LessonsModel;

class AdminLessonsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_all_lessons(Request $request)
    {
        $data = $request->validate([
            'round_content_id' => 'required|exists:round_contents,id',
        ]);

        $lessons = LessonsModel::where('round_content_id', $data['round_content_id'])->get();
        return res_data($lessons, 'success', 200);
    }

    /**
     * All lessons belonging to a round, across all of its round_contents.
     */
    public function get_all_lessons_by_round(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $lessons = LessonsModel::with('round_content:id,title')
            ->whereHas('round_content', function ($query) use ($data) {
                $query->where('round_id', $data['round_id']);
            })
            ->get()
            ->map(function ($lesson) {
                $lesson->round_content_title = $lesson->round_content->title;
                unset($lesson->round_content);
                return $lesson;
            });

        return res_data($lessons, 'success', 200);
    }

    public function store_lesson(StoreLessonRequest $request)
    {
        $data = $request->validated();
        $lesson = LessonsModel::create($data);
        return res_data($lesson, 'success', 200);
    }
    public function edit_lesson(EditLessonRequest $request)
    {
        $data = $request->validated();
        $lesson = LessonsModel::find($data['id']);
        $lesson->update($data);
        return res_data($lesson, 'success', 200);
    }

    public function delete_lesson(DeleteLessonRequest $request)
    {
        $data = $request->validated();
        $lesson = LessonsModel::find($data['id']);
        $lesson->delete();
        return res_data('تم حذف الدرس بنجاح', 'success', 200);
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
