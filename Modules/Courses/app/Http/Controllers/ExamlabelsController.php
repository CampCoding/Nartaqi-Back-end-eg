<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\ExamlabelsModel;

class ExamlabelsController extends Controller
{
    /**
     * Get all exam labels
     */
    public function getExamLabels(Request $request)
    {
        $data = ExamlabelsModel::get();
        return res_data($data, 'success', 200);
    }

    /**
     * Add a new exam label
     */
    public function addExamLabel(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $examLabel = ExamlabelsModel::create($data);
        return res_data($examLabel, 'success', 200);
    }

    /**
     * Update an exam label
     */
    public function updateExamLabel(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_labels,id',
            'label' => 'required|string|max:255',
        ]);

        $examLabel = ExamlabelsModel::find($data['id']);
        $examLabel->update($data);
        return res_data($examLabel, 'success', 200);
    }

    /**
     * Delete an exam label
     */
    public function deleteExamLabel(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:exam_labels,id',
        ]);

        $examLabel = ExamlabelsModel::find($data['id']);
        $examLabel->delete();
        return res_data('success', 'success', 200);
    }
}
