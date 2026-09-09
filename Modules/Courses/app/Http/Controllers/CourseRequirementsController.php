<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CourseRequirementsModel;

class CourseRequirementsController extends Controller
{
    /**
     * Get all course requirements
     */
    public function getCourseRequirements(Request $request)
    {
        $data = CourseRequirementsModel::get();
        return res_data($data, 'success', 200);
    }

    /**
     * Add a new course requirement
     */
    public function addCourseRequirements(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $courseRequirement = CourseRequirementsModel::create($data);
        return res_data($courseRequirement, 'تم إضافة المتطلب بنجاح', 200);
    }

    /**
     * Update a course requirement
     */
    public function updateCourseRequirements(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:course_requirements,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $courseRequirement = CourseRequirementsModel::find($data['id']);

        if (!$courseRequirement) {
            return res_data('المتطلب غير موجود', 'failed', 404);
        }

        $courseRequirement->update($data);
        return res_data($courseRequirement, 'تم تحديث المتطلب بنجاح', 200);
    }

    /**
     * Delete a course requirement
     */
    public function deleteCourseRequirements(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:course_requirements,id',
        ]);

        $courseRequirement = CourseRequirementsModel::find($data['id']);

        if (!$courseRequirement) {
            return res_data('المتطلب غير موجود', 'failed', 404);
        }

        $courseRequirement->delete();
        return res_data('تم حذف المتطلب بنجاح', 'success', 200);
    }
}
