<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\AddRoundFeatureRequest;
use Modules\Courses\Http\Requests\EditRoundFeatureRequest;
use Modules\Courses\Http\Requests\DeleteRoundFeatureRequest;
use Modules\Courses\Models\RoundFeaturesModel;

class RoundFeaturesController extends Controller
{
    /**
     * Get all round features for a specific round
     */
    public function get_all_round_features(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $features = RoundFeaturesModel::where('round_id', $data['round_id'])->get();

        return response()->json([
            'statusCode' => 200,
            'status' => 'success',
            'message' =>  $features
        ], 200);
    }

    /**
     * Add a new round feature
     */
    public function add_round_feature(AddRoundFeatureRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/rounds');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = '/rounds/' . $imageName;
        }

        $feature = RoundFeaturesModel::create($data);

        if ($feature) {
            return response()->json([
                'statusCode' => 201,
                'status' => 'success',
                'message' => 'تم إضافة الميزة بنجاح',
                'data' => [
                    'feature' => $feature
                ]
            ], 201);
        } else {
            return response()->json([
                'statusCode' => 400,
                'status' => 'failed',
                'message' => 'فشل إضافة الميزة',
                'data' => null
            ], 400);
        }
    }

    /**
     * Edit an existing round feature
     */
    public function edit_round_feature(EditRoundFeatureRequest $request)
    {
        $data = $request->validated();
        $feature = RoundFeaturesModel::find($data['id']);

        if (!$feature) {
            return response()->json([
                'statusCode' => 404,
                'status' => 'failed',
                'message' => 'الميزة غير موجودة',
                'data' => null
            ], 404);
        }

        // Handle image upload if provided
        if (request()->hasFile('image')) {
            // Delete old image if exists
            if ($feature->image && file_exists(public_path('storage/' . $feature->image))) {
                unlink(public_path('storage/' . $feature->image));
            }

            $destinationPath = public_path('storage/rounds');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'rounds/' . $imageName;
        }

        $feature->update($data);
        return response()->json([
            'statusCode' => 200,
            'status' => 'success',
            'message' => 'تم تعديل الميزة بنجاح',
            'data' => [
                'feature' => $feature->fresh()
            ]
        ], 200);
    }

    /**
     * Delete a round feature
     */
    public function delete_round_feature(DeleteRoundFeatureRequest $request)
    {
        $data = $request->validated();
        $feature = RoundFeaturesModel::find($data['id']);

        if (!$feature) {
            return response()->json([
                'statusCode' => 404,
                'status' => 'failed',
                'message' => 'الميزة غير موجودة',
                'data' => null
            ], 404);
        }

        // Delete associated image if exists
        if ($feature->image && file_exists(public_path('storage/' . $feature->image))) {
            unlink(public_path('storage/' . $feature->image));
        }

        $feature->delete();
        return response()->json([
            'statusCode' => 200,
            'status' => 'success',
            'message' => 'تم حذف الميزة بنجاح'
        ], 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('courses::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('courses::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('courses::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('courses::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
