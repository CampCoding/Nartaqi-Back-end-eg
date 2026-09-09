<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Http\Requests\CreateCourseCategoryRequest;
use Modules\Courses\Http\Requests\DeleteCourseRequest;
use Modules\Courses\Http\Requests\EditCourseRequest;
use Modules\Courses\Http\Requests\ActiveAndInactivCourseRequest;
use Modules\Courses\Models\CourseCategories;
use Modules\Courses\Models\CategoryPartsModel;
use Modules\Courses\Transformers\CourseCategoriesResource;

class AdminCourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_all_course_categories(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $courses = CourseCategories::orderBy('sort_number', 'asc')->paginate($perPage);
        return res_data($courses, 'success', 200);
    }


    public function makeSortCourseCategories(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:course_categories,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        try {
            \DB::beginTransaction();

            foreach ($data['items'] as $item) {
                CourseCategories::where('id', $item['id'])
                    ->update(['sort_number' => $item['sort_number']]);
            }

            \DB::commit();

            return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return res_data('فشل تحديث الترتيب', 'error', 400);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store_course_category(CreateCourseCategoryRequest $request)
    {
        $data = $request->validated();

        // القيمة الافتراضية للحالة
        $data['active'] = isset($data['active']) ? (int) $data['active'] : 1;

        // تعيين رقم الترتيب تلقائيًا إذا لم يتم إرساله
        if (!isset($data['sort_number'])) {
            $maxSortNumber = CourseCategories::max('sort_number');
            $data['sort_number'] = $maxSortNumber ? $maxSortNumber + 1 : 1;
        }

        $category = CourseCategories::create($data);

        if ($category) {
            return res_data('تم إنشاء التصنيف بنجاح', 'success', 201);
        }

        return res_data('فشل إنشاء التصنيف', 'error', 400);
    }


    /**
     * Show the specified resource.
     */

    public function edit_course_category(EditCourseRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];
        $category = CourseCategories::find($id);
        if (!$category) {
            return  res_data('التصنيف غير موجود', 'error', 404);
        }




        $category->update($data);
        if ($category) {
            return  res_data('تم تعديل التصنيف بنجاح', 'success', 200);
        } else {
            return  res_data('فشل تعديل التصنيف', 'error', 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function active_course_category(ActiveAndInactivCourseRequest $request)
    {
        $data = $request->validated();

        $id = $data['id'];
        $category = CourseCategories::find($id);
        if (!$category) {
            return  res_data('التصنيف غير موجود', 'error', 404);
        }

        // لا تسمح بأن تصبح كل التصنيفات غير نشطة
        // لو الطلب إنه يعطل التصنيف (active = 0) وتبقى هو آخر واحد Active → امنع العملية
        if ((int) $data['active'] === 0) {
            $activeCount = CourseCategories::where('active', 1)->count();

            // لو مافيش غير تصنيف واحد Active وهو ده → رجّع خطأ
            if ($activeCount <= 1 && (int) $category->active === 1) {
                return res_data('يجب أن يكون هناك تصنيف واحد نشط على الأقل، لا يمكن تعطيل جميع التصنيفات', 'error', 400);
            }
        }

        $category->update(['active' => $data['active']]);
        if ($category) {
            return  res_data('تم التعديل حالة التصنيف بنجاح', 'success', 200);
        } else {
            return  res_data('فشل تعديل حالة التصنيف', 'error', 400);
        }
    }


    public function delete_course_category(DeleteCourseRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];
        $category = CourseCategories::find($id);
        if (!$category) {
            return  res_data('التصنيف غير موجود', 'error', 404);
        }

        // Check if category has related category_parts
        $hasCategoryParts = CategoryPartsModel::where('course_category_id', $id)->exists();
        if ($hasCategoryParts) {
            return  res_data('الفئه تحتوي علي اقسام برجاء مسحها اولا', 'error', 400);
        }

        $category->delete();
        if ($category) {
            return  res_data('تم حذف التصنيف بنجاح', 'success', 200);
        } else {
            return  res_data('فشل حذف التصنيف', 'error', 400);
        }
    }

    public function getCourseCategoriesWithParts(Request $request)
    {
        $categories = CourseCategories::with(['category_parts' => function ($query) {
            $query->orderBy('sort_number', 'asc');
        }])
            ->orderBy('sort_number', 'asc')
            ->get();

        return res_data($categories, 'success', 200);
    }
}
