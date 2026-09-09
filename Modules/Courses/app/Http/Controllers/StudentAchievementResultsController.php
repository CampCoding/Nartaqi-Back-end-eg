<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\StudentAchievementResultsModel;
use Modules\Courses\Http\Requests\AddStudentAchievementResultsRequest;
use Modules\Courses\Http\Requests\EditStudentAchievementResultsRequest;
use Modules\Courses\Http\Requests\DeleteStudentAchievementResultsRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\CategoryPartsModel;

class StudentAchievementResultsController extends Controller
{
    /**
     * Get all student achievement results
     */

    public function getAllCategoryPart(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $category_parts = CategoryPartsModel::withCount('student_achievement_results')
            ->orderBy('sort_as_student_degree', 'asc')
            ->paginate($perPage);

        return res_data($category_parts, 'success', 200);
    }

    public function getCategoryPartsSortedForStudentDegree(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10000);
        $query = CategoryPartsModel::withCount('student_achievement_results');


        $category_parts = $query->orderBy('sort_as_student_degree', 'asc')->paginate(10000);
        return res_data($category_parts, 'success', 200);
    }

    public function deletestudent_achievement_results_count(Request $request)
    {
        $data = $request->validate([
            'category_part_id' => 'required|exists:category_parts,id',
        ]);

        // Get all results for this category part
        $results = StudentAchievementResultsModel::where('category_part_id', $data['category_part_id'])->get();

        if ($results->isEmpty()) {
            return res_data('لا توجد سجلات لهذا الجزء', 'error', 404);
        }

        // Delete images for all results
        // foreach ($results as $result) {
        //     if ($result->image && Storage::disk('public')->exists($result->image)) {
        //         Storage::disk('public')->delete($result->image);
        //     }
        // }

        // Delete all results for this category part
        $deletedCount = StudentAchievementResultsModel::where('category_part_id', $data['category_part_id'])->delete();

        return res_data([
            'message' => 'تم حذف جميع السجلات بنجاح',
            'deleted_count' => $deletedCount
        ], 'success', 200);
    }

    public function makeSortCategoryParts(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:category_parts,id',
            'items.*.sort_as_student_degree' => 'required|integer|min:1',
        ]);

        try {
            \DB::beginTransaction();

            foreach ($data['items'] as $item) {
                CategoryPartsModel::where('id', $item['id'])
                    ->update(['sort_as_student_degree' => $item['sort_as_student_degree']]);
            }

            \DB::commit();

            return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return res_data('فشل تحديث الترتيب', 'error', 400);
        }
    }

    public function getAllStudentAchievementResults(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $results = StudentAchievementResultsModel::with('category_part:id,name')
            ->paginate($perPage);

        return res_data($results, 'success', 200);
    }

    /**
     * Get student achievement result by ID
     */
    public function getStudentAchievementResultById(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:student_achievement_results,id',
        ]);

        $result = StudentAchievementResultsModel::with('category_part:id,name')
            ->find($data['id']);

        if (!$result) {
            return res_data('السجل غير موجود', 'error', 404);
        }

        return res_data($result, 'success', 200);
    }

    /**
     * Get student achievement results by category part ID
     */
    public function getStudentAchievementResultsByCategoryPart(Request $request)
    {
        $data = $request->validate([
            'category_part_id' => 'required|exists:category_parts,id',
        ]);

        $results = StudentAchievementResultsModel::where('category_part_id', $data['category_part_id'])
            ->with('category_part:id,name')
            ->orderBy('sort_number', 'asc')
            ->get();

        return res_data($results, 'success', 200);
    }


    public function makeSortStudentAchievementResults(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:student_achievement_results,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        $items = $data['items'];

        foreach ($items as $item) {
            $result = StudentAchievementResultsModel::find($item['id']);
            if ($result) {
                $result->sort_number = $item['sort_number'];
                $result->save();
            }
        }

        return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
    }

    /**
     * Add new student achievement result
     */
    public function addStudentAchievementResults(AddStudentAchievementResultsRequest $request)
    {
        $data = $request->validated();

        // Auto-increment sort_number if not provided
        if (!isset($data['sort_number']) || $data['sort_number'] === null) {
            $maxSortNumber = StudentAchievementResultsModel::where('category_part_id', $data['category_part_id'])
                ->max('sort_number');
            $data['sort_number'] = $maxSortNumber ? $maxSortNumber + 1 : 1;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(storage_path('app/public/student_achievement_results'), $imageName);
            $data['image'] = 'student_achievement_results/' . $imageName;
        }

        $result = StudentAchievementResultsModel::create($data);

        return res_data($result, 'تم إضافة السجل بنجاح', 201);
    }

    /**
     * Edit existing student achievement result
     */
    public function editStudentAchievementResults(EditStudentAchievementResultsRequest $request)
    {
        $data = $request->validated();

        $result = StudentAchievementResultsModel::find($data['id']);

        if (!$result) {
            return res_data('السجل غير موجود', 'error', 404);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($result->image) {
                $oldImagePath = storage_path('app/public/' . $result->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(storage_path('app/public/student_achievement_results'), $imageName);
            $data['image'] = 'student_achievement_results/' . $imageName;
        }

        // Remove id from data before update
        unset($data['id']);

        $result->update($data);

        return res_data($result->fresh(), 'تم تعديل السجل بنجاح', 200);
    }

    /**
     * Delete student achievement result
     */
    public function deleteStudentAchievementResults(DeleteStudentAchievementResultsRequest $request)
    {
        $data = $request->validated();

        $result = StudentAchievementResultsModel::find($data['id']);

        if (!$result) {
            return res_data('السجل غير موجود', 'error', 404);
        }

        // Delete image if exists
        if ($result->image) {
            $imagePath = storage_path('app/public/' . $result->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $result->delete();

        return res_data('تم حذف السجل بنجاح', 'success', 200);
    }
}
