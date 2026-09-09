<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\TconditionsModel;

class TconditionsController extends Controller
{
    /**
     * Get all term conditions with pagination
     */
    public function getTconditions(Request $request)
    {
        $data = TconditionsModel::get();
        return res_data($data, 'success', 200);
    }

    /**
     * Update term conditions by ID
     */
    public function updateTconditions(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:term_conditions,id',
            'content' => 'required|string'
        ]);

        $tcondition = TconditionsModel::find($data['id']);
        $tcondition->update($data);
        return res_data($tcondition, 'success', 200);
    }
}
