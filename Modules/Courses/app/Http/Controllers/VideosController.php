<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\DeleteVideoRequest;
use Modules\Courses\Http\Requests\EditVideoRequest;
use Modules\Courses\Models\FreeVideosModel;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Http\Requests\AddVideoRequest;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\Rounds;

class VideosController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getFreeVideos(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        // Get the round to find category_part_free_id
        $round = Rounds::find($data['round_id']);

        if (!$round || !$round->category_part_free_id) {
            return res_data([], 'لا توجد فيديوهات مجانية لهذه الدورة', 200);
        }

        // Get free videos based on category_part_free_id
        $videos = FreeVideosModel::where('category_part_free_id', $round->category_part_free_id)
            ->get();

        return res_data($videos, 'success', 200);
    }

    public function get_all_videos(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
        ]);

        $videos = VideosModel::where('lesson_id', $data['lesson_id'])->get();
        return res_data($videos, 'success', 200);
    }
    public function add_video(AddVideoRequest $request)
    {
        $data = $request->validated();
        $video = VideosModel::create($data);
        return res_data($video, 'success', 200);
    }
    public function edit_video(EditVideoRequest $request)
    {
        $data = $request->validated();
        $video = VideosModel::find($data['id']);
        $video->update($data);
        return res_data('تم تعديل الفيديو بنجاح', 'success', 200);
    }

    public function delete_video(DeleteVideoRequest $request)
    {
        $data = $request->validated();
        $video = VideosModel::find($data['id']);
        $video->delete();
        return res_data('تم حذف الفيديو بنجاح', 'success', 200);
    }
    public function index()
    {
        return view('courses::index');
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
