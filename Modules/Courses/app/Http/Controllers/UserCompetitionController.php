<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CompetitionsModel;
use Modules\Courses\Models\CompetitionQuestionsModel;
use Carbon\Carbon;
use Modules\Courses\Models\CompetitionQuestionsOptionsModel;
use Modules\Courses\Models\StudentCompetitionModel;
use Modules\Courses\Models\StudentCompetitionAnswersModel;
use Illuminate\Support\Facades\DB;

class UserCompetitionController extends Controller
{

    public function getAllCompetitions(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'student_id' => 'nullable|exists:students,id',
                "type" => 'string'
            ]);

            $perPage = $request->input('per_page', 10);
            $studentId = $request->input('student_id');
            $CompetitionType = $request->input('type', 'daily');
            $query = CompetitionsModel::where('active', '1')->where('type', $CompetitionType)
                ->orderBy('start_date', 'desc');

            $competitions = $query->paginate($perPage);

            $competitions->getCollection()->transform(function ($competition) use ($studentId) {
                $totalQuestions = CompetitionQuestionsModel::where('competition_id', $competition->id)->count();

                $studentsCount = StudentCompetitionModel::where('competition_id', $competition->id)->count();
                $maxQuestions = null;
                if ($competition->type === 'weekly') {
                    $maxQuestions = ($competition->question_type === 'single') ? 7 : 14;
                } elseif ($competition->type === 'monthly') {
                    $maxQuestions = ($competition->question_type === 'single') ? 30 : 60;
                }

                // Check competition status
                $now = Carbon::now();
                $startDate = Carbon::parse($competition->start_date);
                $endDate = Carbon::parse($competition->end_date);

                if ($now->lt($startDate)) {
                    $status = 'upcoming'; // 
                } elseif ($now->between($startDate, $endDate)) {
                    $status = 'ongoing'; // 
                } else {
                    $status = 'ended'; // 
                }

                // Check if student is enrolled (if student_id provided)
                $enrolled = false;
                $studentAnsweredCount = 0;
                if ($studentId) {
                    $enrolled = StudentCompetitionModel::where('student_id', $studentId)
                        ->where('competition_id', $competition->id)
                        ->exists();

                    // Count how many unique questions the student has answered in this competition
                    if ($enrolled) {
                        $studentAnsweredCount = StudentCompetitionAnswersModel::where('student_id', $studentId)
                            ->where('competition_id', $competition->id)
                            ->distinct('question_id')
                            ->count('question_id');
                    }
                }

                // Calculate days remaining
                if ($competition->type === 'daily') {
                    $daysRemaining = 0;
                } else {
                    // Calculate days between now and end_date
                    if ($now->gt($endDate)) {
                        // Competition ended
                        $daysRemaining = 0;
                    } else {
                        // Calculate remaining days
                        $daysRemaining = $now->diffInDays($endDate);
                    }
                }

                // Add computed fields
                $competition->questions_count = $totalQuestions;
                $competition->students_count = $studentsCount;
                $competition->max_questions = $maxQuestions;
                $competition->status = $status;
                $competition->is_complete = $maxQuestions !== null ? ($totalQuestions >= $maxQuestions) : ($totalQuestions > 0);
                $competition->enrolled = $enrolled;
                $competition->student_answered_count = $studentAnsweredCount;
                $competition->days_remaining = $daysRemaining;

                return $competition;
            });

            return response()->json([
                'status' => 'success',
                'data' => $competitions
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب المسابقات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function enrollInCompetition(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'competition_id' => 'required|exists:competitions,id'
            ]);

            $studentId = $request->input('student_id');
            $competitionId = $request->input('competition_id');

            // Check if competition is active
            $competition = CompetitionsModel::where('id', $competitionId)
                ->where('active', '1')
                ->first();

            if (!$competition) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المسابقة غير نشطة أو غير موجودة'
                ], 404);
            }

            // Check if competition has ended
            $now = Carbon::now();
            $endDate = Carbon::parse($competition->end_date);

            if ($now->gt($endDate)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن التسجيل في المسابقة. المسابقة منتهية',
                    'end_date' => $competition->end_date
                ], 400);
            }

            // Check if student already enrolled
            $alreadyEnrolled = \Modules\Courses\Models\StudentCompetitionModel::where('student_id', $studentId)
                ->where('competition_id', $competitionId)
                ->exists();

            if ($alreadyEnrolled) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'أنت مسجل بالفعل في هذه المسابقة'
                ], 400);
            }

            // Enroll student
            $enrollment = \Modules\Courses\Models\StudentCompetitionModel::create([
                'student_id' => $studentId,
                'competition_id' => $competitionId,
                'date_created' => now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم التسجيل في المسابقة بنجاح',
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $studentId,
                    'competition_id' => $competitionId,
                    'competition_name' => $competition->competition_name,
                    'enrolled_at' => $enrollment->date_created
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في التسجيل في المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getCompetitionQuestions(Request $request)
    {
        try {
            $request->validate([
                'competition_id' => 'required|exists:competitions,id',
                'student_id' => 'required|exists:students,id'
            ]);

            $competitionId = $request->input('competition_id');
            $studentId = $request->input('student_id');

            // Get competition details
            $competition = CompetitionsModel::where('id', $competitionId)
                ->where('active', '1')
                ->first();

            if (!$competition) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المسابقة غير نشطة أو غير موجودة'
                ], 404);
            }

            // Check if student is enrolled
            $isEnrolled = \Modules\Courses\Models\StudentCompetitionModel::where('student_id', $studentId)
                ->where('competition_id', $competitionId)
                ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'يجب التسجيل في المسابقة أولاً'
                ], 403);
            }

            // Build query based on competition type
            $query = CompetitionQuestionsModel::where('competition_id', $competitionId)
                ->with('options');

            // Get today's questions for all competition types
            $today = Carbon::now()->format('Y-m-d');
            $questions = $query->where('show_date', $today)->get();

            // Check if student has answered all of today's questions
            if ($questions->count() > 0) {
                $answeredQuestions = StudentCompetitionAnswersModel::where('student_id', $studentId)
                    ->where('competition_id', $competitionId)
                    ->whereIn('question_id', $questions->pluck('id'))
                    ->whereDate('created_at', $today)
                    ->pluck('question_id')
                    ->unique();

                $answeredTodayCount = $answeredQuestions->count();

                if ($answeredTodayCount >= $questions->count()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'تم حل أسئلة اليوم',
                        'data' => [
                            'all_answered' => true,
                            'answered_count' => $answeredTodayCount,
                            'total_questions' => $questions->count()
                        ]
                    ], 200);
                }
            }


            return response()->json([
                'status' => 'success',
                'data' => [
                    'competition' => [
                        'id' => $competition->id,
                        'name' => $competition->competition_name,
                        'type' => $competition->type,
                        'question_type' => $competition->question_type,
                        'start_date' => $competition->start_date,
                        'end_date' => $competition->end_date
                    ],
                    'questions' => $questions,
                    'total_questions' => $questions->count(),
                    'is_daily' => $competition->type === 'daily'
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب أسئلة المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitAllAnswers(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'competition_id' => 'required|exists:competitions,id',
                'answers' => 'required|array|min:1',
                'answers.*.question_id' => 'required',
                'answers.*.answer_text' => 'required|string',
                'answers.*.correct_or_not' => 'required|in:0,1'
            ]);

            $studentId = $request->input('student_id');
            $competitionId = $request->input('competition_id');
            $answers = $request->input('answers');

            // Check if student is enrolled
            $isEnrolled = StudentCompetitionModel::where('student_id', $studentId)
                ->where('competition_id', $competitionId)
                ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'يجب التسجيل في المسابقة أولاً'
                ], 403);
            }

            DB::beginTransaction();

            try {
                $savedAnswers = [];
                $correctCount = 0;
                $wrongCount = 0;

                foreach ($answers as $answerData) {
                    $questionId = $answerData['question_id'];
                    $answerText = $answerData['answer_text'];
                    $correctOrNot = $answerData['correct_or_not'];

                    // Validate question belongs to competition
                    $question = CompetitionQuestionsModel::where('competition_id', $competitionId)
                        ->where('id', $questionId)
                        ->first();

                    if (!$question) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "السؤال رقم {$questionId} غير موجود أو غير مرتبط بالمسابقة"
                        ], 404);
                    }

                    // Check if already answered
                    $existingAnswer = \Modules\Courses\Models\StudentCompetitionAnswersModel::where('student_id', $studentId)
                        ->where('question_id', $questionId)
                        ->exists();

                    if ($existingAnswer) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "لقد أجبت على السؤال رقم {$questionId} من قبل"
                        ], 400);
                    }

                    // Save answer
                    $answer = \Modules\Courses\Models\StudentCompetitionAnswersModel::create([
                        'student_id' => $studentId,
                        'question_id' => $questionId,
                        'competition_id' => $competitionId,
                        'answer_text' => $answerText,
                        'correct_or_not' => $correctOrNot
                    ]);

                    if ($correctOrNot == 1) {
                        $correctCount++;
                    } else {
                        $wrongCount++;
                    }

                    $savedAnswers[] = [
                        'question_id' => $questionId,
                        'answer_id' => $answer->id,
                        'is_correct' => $correctOrNot == 1
                    ];
                }

                DB::commit();

                $totalQuestions = count($answers);
                $score = ($correctCount / $totalQuestions) * 100;

                return response()->json([
                    'status' => 'success',
                    'message' => 'تم حفظ جميع الإجابات بنجاح',
                    'data' => [
                        'total_questions' => $totalQuestions,
                        'correct_answers' => $correctCount,
                        'wrong_answers' => $wrongCount,
                        'score' => round($score, 2),
                        'answers' => $savedAnswers
                    ]
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حفظ الإجابات',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getStudentInfoScoresPoints(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id'
            ]);

            $studentId = $request->input('student_id');

            // Get all correct answers (points) from all competitions
            $correctAnswers = \Modules\Courses\Models\StudentCompetitionAnswersModel::where('student_id', $studentId)
                ->where('correct_or_not', '1')
                ->get();

            // Count total points
            $totalPoints = $correctAnswers->count();

            // Get total answers from all competitions
            $totalAnswers = \Modules\Courses\Models\StudentCompetitionAnswersModel::where('student_id', $studentId)
                ->count();

            // Get wrong answers
            $wrongAnswers = $totalAnswers - $totalPoints;

            // Calculate overall score percentage
            $scorePercentage = $totalAnswers > 0 ? round(($totalPoints / $totalAnswers) * 100, 2) : 0;

            // Get competitions the student is enrolled in
            $enrolledCompetitions = StudentCompetitionModel::where('student_id', $studentId)
                ->with('competition')
                ->get();

            $competitionsCount = $enrolledCompetitions->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'student_id' => $studentId,
                    'total_points' => $totalPoints,
                    'total_answers' => $totalAnswers,
                    'wrong_answers' => $wrongAnswers,
                    'score_percentage' => $scorePercentage,
                    'enrolled_competitions_count' => $competitionsCount,
                    'enrolled_competitions' => $enrolledCompetitions
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب معلومات الطالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTopStudentsByCompetitionType(Request $request)
    {
        try {
            $competitionTypes = ['daily', 'weekly', 'monthly'];
            $leaderboard = [];

            foreach ($competitionTypes as $type) {
                // Get all competitions of this type
                $competitions = CompetitionsModel::where('type', $type)
                    ->where('active', '1')
                    ->pluck('id');

                if ($competitions->isEmpty()) {
                    $leaderboard[$type] = [];
                    continue;
                }

                // Get all unique enrolled students
                $enrolledStudents = StudentCompetitionModel::whereIn('competition_id', $competitions)
                    ->select('student_id')
                    ->distinct()
                    ->get();

                // Calculate total points for each student across all competitions of this type
                $studentsWithPoints = $enrolledStudents->map(function ($enrollment) use ($competitions) {
                    $studentId = $enrollment->student_id;

                    // Count total correct answers (points) across all competitions of this type
                    $points = \Modules\Courses\Models\StudentCompetitionAnswersModel::whereIn('competition_id', $competitions)
                        ->where('student_id', $studentId)
                        ->where('correct_or_not', '1')
                        ->count();

                    // Only include students who have at least 1 point
                    if ($points == 0) {
                        return null;
                    }

                    // Get earliest enrollment date for this student in competitions of this type
                    $earliestEnrollment = StudentCompetitionModel::where('student_id', $studentId)
                        ->whereIn('competition_id', $competitions)
                        ->orderBy('date_created', 'asc')
                        ->first();

                    return [
                        'student_id' => $studentId,
                        'total_points' => $points,
                        'enrollment_date' => $earliestEnrollment ? $earliestEnrollment->date_created : null
                    ];
                })->filter()->values();

                // Sort by points DESC, then by enrollment date ASC (earliest first)
                $sortedStudents = $studentsWithPoints->sortBy([
                    ['total_points', 'desc'],
                    ['enrollment_date', 'asc']
                ])->values();

                // Take top 3 and get student details
                $topStudents = $sortedStudents->take(3)->map(function ($item, $index) {
                    $student = \Modules\Authentication\Models\Student::find($item['student_id']);

                    if (!$student) {
                        return null;
                    }

                    return [
                        'rank' => $index + 1,
                        'student_id' => $student->id,
                        'student_name' => $student->name ?? null,
                        'image_url' => $student->image_url ?? null,
                        'gender' => $student->gender ?? null,
                        'total_points' => $item['total_points'],
                        'enrollment_date' => $item['enrollment_date']
                    ];
                })->filter()->values();

                $leaderboard[$type] = $topStudents;
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'daily' => $leaderboard['daily'],
                    'weekly' => $leaderboard['weekly'],
                    'monthly' => $leaderboard['monthly']
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب لوحة المتصدرين',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
