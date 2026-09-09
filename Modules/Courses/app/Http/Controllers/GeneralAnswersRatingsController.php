<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\GeneralAnswersRatingsModel;
use Modules\Courses\Models\GeneralRatingsModel;
use Modules\Courses\Http\Requests\StoreGeneralAnswerRatingRequest;
use Modules\Courses\Http\Requests\UpdateGeneralAnswerRatingRequest;
use Modules\Courses\Http\Requests\DeleteGeneralAnswerRatingRequest;
use Illuminate\Support\Facades\Log;

class GeneralAnswersRatingsController extends Controller
{
    /**
     * Display all answers for a specific rating question.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'general_rating_id' => 'required|exists:general_ratings,id',
        ]);

        $answers = GeneralAnswersRatingsModel::where('general_rating_id', $data['general_rating_id'])
            ->with('generalRatings')
            ->get();

        return res_data($answers, 'success', 200);
    }

    /**
     * Store a newly created answer for a rating question.
     */
    public function store(StoreGeneralAnswerRatingRequest $request)
    {
        $data = $request->validated();

        try {
            $answer = GeneralAnswersRatingsModel::create([
                'general_rating_id' => $data['general_rating_id'],
                'answer' => $data['answer'],
            ]);

            // Load the rating relationship
            $answer->load('generalRatings');

            return res_data($answer, 'تم إضافة الإجابة بنجاح', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create answer rating: ' . $e->getMessage());
            return res_data('فشل إضافة الإجابة: ' . $e->getMessage(), 'error', 400);
        }
    }

    /**
     * Display the specified answer.
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:general_answers_ratings,id',
        ]);

        $answer = GeneralAnswersRatingsModel::with('generalRatings')
            ->find($data['id']);

        if (!$answer) {
            return res_data('الإجابة غير موجودة', 'error', 404);
        }

        return res_data($answer, 'success', 200);
    }

    /**
     * Update the specified answer.
     */
    public function update(UpdateGeneralAnswerRatingRequest $request)
    {
        $data = $request->validated();

        try {
            $answer = GeneralAnswersRatingsModel::find($data['id']);

            if (!$answer) {
                return res_data('الإجابة غير موجودة', 'error', 404);
            }

            $answer->update([
                'answer' => $data['answer'],
            ]);

            // Load the rating relationship
            $answer->load('generalRatings');

            return res_data($answer, 'تم تعديل الإجابة بنجاح', 200);
        } catch (\Exception $e) {
            Log::error('Failed to update answer rating: ' . $e->getMessage());
            return res_data('فشل تعديل الإجابة: ' . $e->getMessage(), 'error', 400);
        }
    }

    /**
     * Remove the specified answer from storage.
     */
    public function destroy(DeleteGeneralAnswerRatingRequest $request)
    {
        $data = $request->validated();

        $answer = GeneralAnswersRatingsModel::find($data['id']);

        if (!$answer) {
            return res_data('الإجابة غير موجودة', 'error', 404);
        }

        try {
            $answer->delete();

            return res_data('تم حذف الإجابة بنجاح', 'success', 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete answer rating: ' . $e->getMessage());
            return res_data('فشل حذف الإجابة: ' . $e->getMessage(), 'error', 400);
        }
    }
}
