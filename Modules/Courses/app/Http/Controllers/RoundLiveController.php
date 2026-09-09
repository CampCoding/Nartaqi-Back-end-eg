<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\EditRoundLiveRequest;
use Modules\Courses\Http\Requests\StoreRoundLiveRequest;
use Modules\Courses\Models\RoundLiveModel;

class RoundLiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function get_all_round_lives(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
        ]);
        $round_lives = RoundLiveModel::where('lesson_id', $data['lesson_id'])->get();
        return res_data($round_lives, 'success', 200);
    }
    public function store_round_live(StoreRoundLiveRequest $request)
    {
        $data = $request->validated();
        $round_live = RoundLiveModel::create($data);
        if (!$round_live) {
            return res_data('فشل إضافة البث المباشر', 'error', 400);
        }
        return res_data($round_live, 'success', 200);
    }
    public function edit_round_live(EditRoundLiveRequest $request)
    {
        $data = $request->validated();
        $round_live = RoundLiveModel::find($data['id']);
        $round_live->update($data);
        return res_data($round_live, 'success', 200);
    }

    public function delete_round_live(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);
        $round_live = RoundLiveModel::find($data['id']);
        if (!$round_live) {
            return res_data('البث المباشر غير موجود', 'error', 404);
        }
        $round_live->delete();
        return res_data('تم حذف البث المباشر بنجاح', 'success', 200);
    }
    public function activeInActiveRoundLive(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'active' => 'required|integer|in:0,1',
        ]);
        $round_live = RoundLiveModel::find($data['id']);
        $round_live->update(['active' => $data['active']]);
        return res_data('تم تعديل حالة البث المباشر بنجاح', 'success', 200);
    }
    public function makeLiveFinished(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);
        $round_live = RoundLiveModel::find($data['id']);

        if (!$round_live) {
            return res_data('البث المباشر غير موجود', 'error', 404);
        }
        $round_live->update(['finished' => "1", 'active' => "0", 'link' => null]);
        if (!$round_live) {
            return res_data('فشل إنهاء البث المباشر', 'error', 400);
        }
        return res_data('تم إنهاء البث المباشر بنجاح', 'success', 200);
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
