<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\TeacherModel;
use Modules\Courses\Http\Requests\AddTeacher;
use Modules\Courses\Http\Requests\EditTeacher;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getTeachers()
    {
        $teachers = TeacherModel::all();
        $teachers->each(function ($teacher) {
            $teacher->image_url = url('storage/' . $teacher->image);
        });
        return res_data($teachers, 'success', 200);
    }


    public function add_teacher(AddTeacher $request)
    {
        $data = $request->validated();
        // echo json_encode( $data);
        // return ;

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'teachers');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'teachers/' . $imageName;
        }
        $teacher = TeacherModel::create($data);
        return res_data($teacher, 'success', 200);
    }
    public function edit_teacher(EditTeacher $request)
    {
        $data = $request->validated();

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'teachers');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'teachers/' . $imageName;
        }

        $teacher = TeacherModel::find($data['id']);
        $teacher->update($data);
        $teacher->refresh(); // Reload the model to get the image_url attribute
        return res_data($teacher, 'success', 200);
    }
    public function delete_teacher(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:teachers,id',
        ]);
        $teacher = TeacherModel::find($data['id']);
        if (!$teacher) {
            return res_data('هذا المدرس غير موجود', 'failed', 404);
        }
        $teacher->delete();
        return res_data('تم حذف المدرس بنجاح', 'success', 200);
    }
}
