<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Courses\Models\SupportGateModel;
use Modules\Authentication\Models\Student;
use Modules\Courses\Http\Requests\UpdateStudentPasswordRequest;

class SupportGateController extends Controller
{
    public function getSupportGate(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $data = SupportGateModel::paginate($perPage);
        return res_data($data, 'success', 200);
    }

    public function addSupportGate(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'youtube_link' => 'required|string',
            'description' => 'nullable',
        ]);

        $supportGate = SupportGateModel::create($data);
        return res_data($supportGate, 'success', 200);
    }

    public function updateSupportGate(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:support,id',
            'title' => 'required|string|max:255',
            'youtube_link' => 'required|string',
            'description' => 'nullable',
        ]);

        $supportGate = SupportGateModel::find($data['id']);
        $supportGate->update($data);
        return res_data($supportGate, 'success', 200);
    }

    public function deleteSupportGate(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:support,id',
        ]);

        $supportGate = SupportGateModel::find($data['id']);
        $supportGate->delete();
        return res_data('success', 'success', 200);
    }

    /**
     * Update student password
     * @param UpdateStudentPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStudentPassword(UpdateStudentPasswordRequest $request)
    {
        $data = $request->validated();

        $student = Student::find($data['student_id']);

        if (!$student) {
            return res_data('الطالب غير موجود', 'failed', 404);
        }

        // Update password with hashing
        $student->password = Hash::make($data['new_password']);
        $student->save();

        return res_data([
            'id' => $student->id,
            'name' => $student->name,
        ], 'تم تحديث كلمة المرور بنجاح', 200);
    }
}
