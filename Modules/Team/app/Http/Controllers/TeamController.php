<?php

namespace Modules\Team\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Team\Models\Team;
use App\Http\Controllers\Controller;
use Modules\Team\Http\Requests\TeamRequest;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('team::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('team::create');
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
        return view('team::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('team::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function getTeam()
    {
        $team = Team::where('hidden', 0)->get();
        return res_data($team, 'success', 200);
    }

    public function getAdminTeam()
    {
        $team = Team::all();
        return res_data($team, 'success', 200);
    }

    public function addTeam(TeamRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('team', 'public');
            $data['image'] = $path;
        }
        $team = Team::create($data);
        return res_data($team, 'تم الاضافة بنجاح', 200);
    }

    public function updateTeam(TeamRequest $request)
    {
        $data = $request->validated();

        $validatedId = $request->validate([
            'id' => 'required|integer',
        ]);

        $team = Team::find($validatedId['id']);
        if (!$team) {
            return res_data('هذا العضو غير موجود', 'failed', 404);
        }
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('team', 'public');
            $data['image'] = $path;
        }
        $team->update($data);
        return res_data($team, 'تم التعديل بنجاح', 200);
    }

    public function deleteTeam(Request $request)
    {
        $team = Team::find($request->id);
        if (!$team) {
            return res_data('العضو غير موجود', 404);
        }
        $team->delete();
        return res_data('تم الحذف بنجاح', 200);
    }

    public function showHideTeam(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $team = Team::find($data['id']);
        if (!$team) {
            return res_data('هذا العضو غير موجود', 'failed', 404);
        }
        $team->hidden = $team->hidden == 1 ? 0 : 1;
        $team->save();

        $message = $team->hidden == 0 ? 'تم إظهار العضو بنجاح' : 'تم إخفاء العضو بنجاح';
        return res_data($message, 'success', 200);
    }
}
