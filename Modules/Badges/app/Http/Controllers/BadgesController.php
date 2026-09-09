<?php

namespace Modules\Badges\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Badges\Models\Badge;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Models\Student;
use Modules\Badges\Http\Requests\BadgeRequest;
use Modules\Badges\Models\StudentBadge;

class BadgesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('badges::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('badges::create');
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
        return view('badges::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('badges::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function getAllBadges() {
        $badges = Badge::all();
        return res_data($badges,'success', 200);
    }

    public function getStudentBadgesByAdmin(Request $request) {

        $student_id = $request->student_id;
        $badges = StudentBadge::with('student','badge','round')->where('student_id',$student_id)->get();
        return res_data($badges,'success', 200);
    }



    public function createBadge(BadgeRequest $request) {

        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('badges', 'public');
            $data['image'] = config('app.url') . '/storage/app/public/' . $path;
        }
        $badge = Badge::create($data);
        return res_data($badge,'تم انشاء الشارة بنجاح', 200);
    }

    public function updateBadge(BadgeRequest $request) {

        $data = $request->validated();

        $validatedId = $request->validate([
            'id' => 'required|exists:badges,id',
        ]);

        $badge = Badge::find($validatedId['id']);
        if(!$badge){
            return res_data('هذه الشارة غير موجود','failed',404);
        }
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('badges', 'public');
            $data['image'] = config('app.url') . '/storage/app/public/' . $path;
        }
        $badge->update($data);

        return res_data($badge,'تم تحديث الشارة بنجاح', 200);
    }

    public function deleteBadge(Request $request){

        $validatedId = $request->validate([
            'id' => 'required',
        ]);

        $badge = Badge::find($validatedId['id']);
        if(!$badge){
            return res_data('هذه الشارة غير موجود','failed',404);
        }
        $badge->delete();
        return res_data('تم الحذف بنجاح','sucess',200);
    }

    public function assignBadgeToStudent(Request $request) {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'badge_id' => 'required|exists:badges,id',
            'round_id' => 'required|exists:rounds,id',
        ]);

        if ($validator->fails()) {
            return res_data('بيانات غير صحيحة', 400);
        }

        $studentId = $request->input('student_id');
        $badgeId = $request->input('badge_id');
        $roundId = $request->input('round_id');

        $badge = Badge::find($badgeId);
        if(!$badge) {
            return res_data('الشارة غير موجودة', 404);
        }

        $alreadyHas = StudentBadge::
            where('student_id', $studentId)
            ->where('round_id', $roundId)
            ->exists();

        if ($alreadyHas) {
            return res_data('الطالب يمتلك هذه الشارة لهذه الدورة بالفعل', 400);
        }

        $badge->students()->attach($studentId, ['round_id' => $roundId]);
        return res_data('تم اضافة الشارة للطالب بنجاح', 200);
    }

    public function getStudentBadges(Request $request)
    {

        $validated['student_id'] = $request->user()->id;

        $studentBadges = StudentBadge::with('badge')->where('student_id', $validated['student_id'])->get();

        if (!$studentBadges) {
            return res_data([], 'هذا الطالب لا يملك أي شارات', 404);
        }

        return res_data($studentBadges, 'تم جلب الشارات بنجاح', 200);
    }

}
