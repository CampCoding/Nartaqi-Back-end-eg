<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\PlacementTestModel;
use Modules\Courses\Models\CategoryPartsModel;

class PlacementTestController extends Controller
{
    public function getCategoryPartsSortedForPlacementTest(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10000);
        $query = CategoryPartsModel::query();



        $category_parts = $query->orderBy('sort_as_placement_test', 'asc')->paginate(10000);
        return response()->json(['status' => true, 'data' => $category_parts]);
    }

    public function makeSort(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:category_parts,id',
            'items.*.sort_as_placement_test' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            foreach ($data['items'] as $item) {
                CategoryPartsModel::where('id', $item['id'])
                    ->update(['sort_as_placement_test' => $item['sort_as_placement_test']]);
            }

            DB::commit();

            return response()->json(['status' => true, 'message' => 'تم تحديث الترتيب بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'فشل تحديث الترتيب'], 400);
        }
    }

    public function getPlacementTestByCategoryPart(Request $request)
    {
        $tests = PlacementTestModel::where('category_part_id', $request->category_part_id)->get();
        return response()->json(['status' => true, 'data' => $tests]);
    }

    public function storePlacementTest(Request $request)
    {
        $test = PlacementTestModel::create($request->all());
        return response()->json(['status' => true, 'message' => 'Placement Test Created Successfully', 'data' => $test]);
    }

    public function editPlacementTest(Request $request)
    {
        $test = PlacementTestModel::find($request->id);
        if (!$test) {
            return response()->json(['status' => false, 'message' => 'Placement Test Not Found'], 404);
        }
        $test->update($request->all());
        return response()->json(['status' => true, 'message' => 'Placement Test Updated Successfully', 'data' => $test]);
    }

    public function deletePlacementTest(Request $request)
    {
        $test = PlacementTestModel::find($request->id);
        if (!$test) {
            return response()->json(['status' => false, 'message' => 'Placement Test Not Found'], 404);
        }
        $test->delete();
        return response()->json(['status' => true, 'message' => 'Placement Test Deleted Successfully']);
    }
}
