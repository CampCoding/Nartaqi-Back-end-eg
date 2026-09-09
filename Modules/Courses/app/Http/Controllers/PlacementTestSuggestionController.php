<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\PlacementTestSuggestionModel;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\TeachersModel;
use Modules\Courses\Http\Requests\StorePlacementTestSuggestionRequest;
use Modules\Courses\Http\Requests\UpdatePlacementTestSuggestionRequest;

class PlacementTestSuggestionController extends Controller
{
    public function storeSuggestion(StorePlacementTestSuggestionRequest $request)
    {
        $data = $request->validated();

        $suggestion = PlacementTestSuggestionModel::create($data);

        return res_data($suggestion, 'Suggestion created successfully', 201);
    }

    public function editSuggestion(UpdatePlacementTestSuggestionRequest $request)
    {
        $data = $request->validated();

        $suggestion = PlacementTestSuggestionModel::find($data['id']);
        $suggestion->update($data);

        return res_data($suggestion, 'Suggestion updated successfully', 200);
    }

    public function deleteSuggestion(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:placement_test_suggestion,id',
        ]);

        PlacementTestSuggestionModel::find($data['id'])->delete();

        return res_data([], 'Suggestion deleted successfully', 200);
    }

    public function selectSuggestionByPlacementTest(Request $request)
    {
        $data = $request->validate([
            'placement_test_id' => 'required|exists:placement_test,id',
        ]);

        $suggestions = PlacementTestSuggestionModel::where('placement_test_id', $data['placement_test_id'])
            ->with(['suggestionRound' => function ($query) {
                $query->select('id', 'name');
            }])
            ->get();

        return res_data($suggestions, 'Suggestions retrieved successfully', 200);
    }

    public function get_all_rounds(Request $request)
    {
        $rounds = Rounds::where('source', '0')
            ->select('id', 'name')
            ->orderBy('created_at', 'desc')
            ->get();

        return res_data($rounds, 'success', 200);
    }
}
