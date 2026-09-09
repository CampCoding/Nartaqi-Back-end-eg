<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\CategryPartFreeModel;
use Modules\Courses\Http\Requests\StoreCategoryPartFreeRequest;
use Modules\Courses\Http\Requests\UpdateCategoryPartFreeRequest;
use Modules\Courses\Http\Requests\DeleteCategoryPartFreeRequest;

class CategoryPartFreeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_all(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $categoryParts = CategryPartFreeModel::orderBy('sort_number', 'asc')->paginate($perPage);
        return res_data($categoryParts, 'success', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryPartFreeRequest $request)
    {
        $data = $request->validated();

        // تعيين رقم الترتيب تلقائيًا إذا لم يتم إرساله
        if (!isset($data['sort_number'])) {
            $maxSortNumber = CategryPartFreeModel::max('sort_number');
            $data['sort_number'] = $maxSortNumber ? $maxSortNumber + 1 : 1;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('category_parts_for_free', 'public');
            $data['image'] = $imagePath;
        }

        $categoryPart = CategryPartFreeModel::create($data);

        if ($categoryPart) {
            return res_data($categoryPart, 'تم إنشاء القسم بنجاح', 201);
        } else {
            return res_data(null, 'فشل إنشاء القسم', 400);
        }
    }

    public function makeSortPartFree(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:category_parts_for_free,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            foreach ($data['items'] as $item) {
                CategryPartFreeModel::where('id', $item['id'])
                    ->update(['sort_number' => $item['sort_number']]);
            }

            DB::commit();

            return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return res_data('فشل تحديث الترتيب', 'error', 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryPartFreeRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];

        $categoryPart = CategryPartFreeModel::find($id);
        if (!$categoryPart) {
            return res_data(null, 'القسم غير موجود', 404);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($categoryPart->image && Storage::disk('public')->exists($categoryPart->image)) {
                Storage::disk('public')->delete($categoryPart->image);
            }

            $image = $request->file('image');
            $imagePath = $image->store('category_parts_for_free', 'public');
            $data['image'] = $imagePath;
        }

        $categoryPart->update($data);

        if ($categoryPart) {
            return res_data($categoryPart, 'تم تعديل القسم بنجاح', 200);
        } else {
            return res_data(null, 'فشل تعديل القسم', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(DeleteCategoryPartFreeRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];

        $categoryPart = CategryPartFreeModel::find($id);
        if (!$categoryPart) {
            return res_data(null, 'القسم غير موجود', 404);
        }

        // Delete image if exists
        if ($categoryPart->image && Storage::disk('public')->exists($categoryPart->image)) {
            Storage::disk('public')->delete($categoryPart->image);
        }

        $categoryPart->delete();

        return res_data('تم حذف القسم بنجاح', 200);
    }
}
