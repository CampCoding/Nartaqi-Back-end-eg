<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Http\Requests\AddCategoryParts;
use Modules\Courses\Http\Requests\DeleteCategoryParts;
use Modules\Courses\Http\Requests\EditCategoryParts;
use Modules\Courses\Http\Requests\GetCategoryPartsByCourseCategoryId;
use Modules\Courses\Models\CategoryPartsModel;

class CategoryPartsController extends Controller
{
    /**
     * Display a listing of the resource.
     */



    public function add_category_part(AddCategoryParts $request)
    {
        $data = $request->validated();
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'category_parts');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'category_parts' . '/' . $imageName;
        }

        // Auto-assign sort_number based on course_category_id
        $lastPart = CategoryPartsModel::where('course_category_id', $data['course_category_id'])
            ->orderBy('sort_number', 'desc')
            ->first();
        $data['sort_number'] = $lastPart ? ((int) $lastPart->sort_number + 1) : 1;

        $category_part = CategoryPartsModel::create($data);
        return res_data($category_part, 'تم الاضافة بنجاح', 201);
    }

    public function makeSort(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:category_parts,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        try {
            \DB::beginTransaction();

            foreach ($data['items'] as $item) {
                CategoryPartsModel::where('id', $item['id'])
                    ->update(['sort_number' => $item['sort_number']]);
            }

            \DB::commit();

            return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return res_data('فشل تحديث الترتيب', 'error', 400);
        }
    }
    public function edit_category_part(EditCategoryParts $request)
    {
        $data = $request->validated();
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'category_parts');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'category_parts' . '/' . $imageName;
        }
        $category_part = CategoryPartsModel::find($data['id']);
        $category_part->update($data);
        return res_data($category_part, 'تم التعديل بنجاح', 200);
    }
    public function delete_category_part(DeleteCategoryParts $request)
    {
        $data = $request->validated();
        $category_part = CategoryPartsModel::find($data['id']);
        $category_part->delete();
        return res_data('تم حذف التصنيف بنجاح', 'success', 200);
    }

    public function get_category_parts_by_course_category_id(GetCategoryPartsByCourseCategoryId $request)
    {
        $data = $request->validated();
        $category_parts = CategoryPartsModel::where('course_category_id', $data['course_category_id'])
            ->orderBy('sort_number', 'asc')
            ->get();
        return res_data($category_parts, 'تم الحصول على التصنيفات بنجاح', 200);
    }
}
