<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\GeneralRatingsModel;
use Modules\Courses\Models\GeneralAnswersRatingsModel;
use Modules\Courses\Models\StudentGeneralRatingAnswerModel;
use Modules\Courses\Http\Requests\StoreGeneralRatingRequest;
use Modules\Courses\Http\Requests\UpdateGeneralRatingRequest;
use Modules\Courses\Http\Requests\DeleteGeneralRatingRequest;
use Modules\Courses\Http\Requests\StoreStudentGeneralRatingAnswersRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Courses\Models\CourseRequirementsModel;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Models\SupportGateModel;
use Modules\Courses\Models\StudentinquiryModel;

class GeneralRatingsController extends Controller
{
    /**
     * Display a listing of all general ratings with their answers.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $ratings = GeneralRatingsModel::with('generalAnswersRatings')
            ->paginate($perPage);

        return res_data($ratings, 'success', 200);
    }

    public function get_support_gate(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $data = SupportGateModel::paginate($perPage);
        return res_data($data, 'success', 200);
    }

    public function getCourseRequirements(Request $request)
    {
        $data = CourseRequirementsModel::get();
        return res_data($data, 'success', 200);
    }



    /**
     * Store a newly created rating question with its answers.
     */
    public function store(StoreGeneralRatingRequest $request)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            // Create the rating question
            $rating = GeneralRatingsModel::create([
                'question' => $data['question'],
            ]);

            // Create answer options
            foreach ($data['answers'] as $answer) {
                GeneralAnswersRatingsModel::create([
                    'general_rating_id' => $rating->id,
                    'answer' => $answer,
                ]);
            }

            DB::commit();

            // Load the answers relationship
            $rating->load('generalAnswersRatings');

            return res_data($rating, 'تم إنشاء التقييم بنجاح', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to create general rating: ' . $e->getMessage());
            return res_data('فشل إنشاء التقييم: ' . $e->getMessage(), 'error', 400);
        }
    }

    /**
     * Display the specified rating with its answers.
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:general_ratings,id',
        ]);

        $rating = GeneralRatingsModel::with('generalAnswersRatings')
            ->find($data['id']);

        if (!$rating) {
            return res_data('التقييم غير موجود', 'error', 404);
        }

        return res_data($rating, 'success', 200);
    }

    /**
     * Update the specified rating question and optionally its answers.
     */
    public function update(UpdateGeneralRatingRequest $request)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $rating = GeneralRatingsModel::find($data['id']);

            if (!$rating) {
                return res_data('التقييم غير موجود', 'error', 404);
            }

            // Update the question
            $rating->update([
                'question' => $data['question'],
            ]);

            // If answers are provided, replace all existing answers
            if (isset($data['answers'])) {
                // Delete old answers
                GeneralAnswersRatingsModel::where('general_rating_id', $rating->id)->delete();

                // Create new answers
                foreach ($data['answers'] as $answer) {
                    GeneralAnswersRatingsModel::create([
                        'general_rating_id' => $rating->id,
                        'answer' => $answer,
                    ]);
                }
            }

            DB::commit();

            // Load the answers relationship
            $rating->load('generalAnswersRatings');

            return res_data($rating, 'تم تعديل التقييم بنجاح', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return res_data('فشل تعديل التقييم', 'error', 400);
        }
    }

    /**
     * Remove the specified rating and its answers from storage.
     */
    public function destroy(DeleteGeneralRatingRequest $request)
    {
        $data = $request->validated();

        $rating = GeneralRatingsModel::find($data['id']);

        if (!$rating) {
            return res_data('التقييم غير موجود', 'error', 404);
        }

        try {
            // Delete associated answers first
            GeneralAnswersRatingsModel::where('general_rating_id', $rating->id)->delete();

            // Delete the rating
            $rating->delete();

            return res_data('تم حذف التقييم بنجاح', 'success', 200);
        } catch (\Exception $e) {
            return res_data('فشل حذف التقييم', 'error', 400);
        }
    }

    public function getGeneralRatingsWithAnswers(Request $request)
    {
        $ratings = GeneralRatingsModel::with('generalAnswersRatings')->get();
        return res_data($ratings, 'success', 200);
    }

    public function studentGeneralRatingAnswers(StoreStudentGeneralRatingAnswersRequest $request)
    {
        $data = $request->validated();

        StudentGeneralRatingAnswerModel::where('student_id', $data['student_id'])->delete();

        $answersData = collect($data['answers'])->map(function ($answer) use ($data) {
            return [
                'student_id' => $data['student_id'],
                'general_rating_id' => $answer['general_rating_id'],
                'general_answer_rating_id' => $answer['general_answer_rating_id']

            ];
        })->toArray();


        StudentGeneralRatingAnswerModel::insert($answersData);

        return res_data('تم حفظ الإجابات بنجاح', 'success', 200);
    }





    public function GetStudentExamsWithScores(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studentScores = StudentScoreModel::where('student_id', $data['student_id'])
            ->with('exam')
            ->get()
            ->filter(function ($score) {
                return $score->exam !== null; // Filter out scores where exam has been deleted
            });

        // Group scores by exam
        $groupedByExam = $studentScores->groupBy('exam_id');

        $exams = $groupedByExam->map(function ($scores, $examId) {
            $exam = $scores->first()->exam;
            $timesScored = $scores->map(function ($score) {
                return [
                    'id' => $score->id,
                    'score' => $score->score,
                    'created_at' => $score->created_at,
                ];
            })->values();

            return [
                'id' => $exam->id,
                'exam_name' => $exam->title,
                'exam_type' => $exam->type,
                'level' => $exam->level,
                'times_scored' => $timesScored,
            ];
        })->values();

        return res_data($exams, 'success', 200);
    }

    /**
     * Get all student inquiries with pagination
     */
    public function getStudentInquiries(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1',
        ]);

        $perPage = (int) $request->get('per_page', 10);
        $inquiries = StudentinquiryModel::orderBy('id', 'desc')->paginate($perPage);

        return res_data($inquiries, 'success', 200);
    }

    /**
     * Mark inquiry as solved (automatically sets solved to 1)
     */
    public function markInquiryAsSolved(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:student_inquiry,id',
        ]);

        $inquiry = StudentinquiryModel::find($data['id']);

        if (!$inquiry) {
            return res_data('الاستفسار غير موجود', 'failed', 404);
        }

        $inquiry->solved = '1';
        $inquiry->save();

        return res_data($inquiry, 'تم وضع علامة على الاستفسار كمحلول', 200);
    }
}
