<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Modules\Courses\Models\PlacementTestSectionsModel;

class PlacementTestSectionsController extends Controller
{
    public function getSectionByExam(Request $request)
    {
        $sections = PlacementTestSectionsModel::where('placement_test_id', $request->placement_test_id)->get();
        return response()->json(['status' => true, 'data' => $sections]);
    }

    public function storeSection(Request $request)
    {
        $section = PlacementTestSectionsModel::create($request->all());
        return response()->json(['status' => true, 'message' => 'Section Created Successfully', 'data' => $section]);
    }

    public function editSection(Request $request)
    {
        $section = PlacementTestSectionsModel::find($request->id);
        if (!$section) {
            return response()->json(['status' => false, 'message' => 'Section Not Found'], 404);
        }
        $section->update($request->all());
        return response()->json(['status' => true, 'message' => 'Section Updated Successfully', 'data' => $section]);
    }

    public function deleteSection(Request $request)
    {
        $section = PlacementTestSectionsModel::find($request->id);
        if (!$section) {
            return response()->json(['status' => false, 'message' => 'Section Not Found'], 404);
        }
        $section->delete();
        return response()->json(['status' => true, 'message' => 'Section Deleted Successfully']);
    }
}
