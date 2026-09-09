<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\HonorRollModel;
use Illuminate\Support\Facades\DB;

class HonorRollController extends Controller
{
    /**
     * Get all honor rolls sorted by sort_number
     */
    public function getHonorRolls(Request $request)
    {
        $data = HonorRollModel::orderBy('sort_number', 'asc')->get();
        return res_data($data, 'success', 200);
    }

    /**
     * Add new student to honor roll
     */
    public function storeHonorRoll(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'title' => 'nullable|string',
            'degree' => 'nullable|string',
        ]);

        // تعيين رقم الترتيب تلقائيًا (أكبر رقم + 1)
        $maxSortNumber = HonorRollModel::max('sort_number');
        $data['sort_number'] = $maxSortNumber ? $maxSortNumber + 1 : 1;

        $honorRoll = HonorRollModel::create($data);
        return res_data($honorRoll, 'success', 201);
    }

    /**
     * Update honor roll entry
     */
    public function updateHonorRoll(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:honor_rolls,id',
            'name' => 'sometimes|string',
            'title' => 'nullable|string',
            'degree' => 'nullable|string',
        ]);

        $honorRoll = HonorRollModel::findOrFail($data['id']);
        $honorRoll->update($data);
        
        return res_data($honorRoll, 'success', 200);
    }

    /**
     * Make custom sort for honor rolls
     */
    public function makeSortHonorRoll(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:honor_rolls,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            foreach ($data['items'] as $item) {
                HonorRollModel::where('id', $item['id'])
                    ->update(['sort_number' => $item['sort_number']]);
            }

            DB::commit();

            return res_data(null, 'تم تحديث الترتيب بنجاح', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return res_data(null, 'فشل تحديث الترتيب', 400);
        }
    }

    /**
     * Delete from honor roll
     */
    public function deleteHonorRoll(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:honor_rolls,id'
        ]);

        HonorRollModel::destroy($data['id']);
        
        return res_data(null, 'deleted successfully', 200);
    }
}
