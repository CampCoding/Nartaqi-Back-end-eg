<?php

namespace Modules\Courses\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Courses\Models\Rounds;
use App\Http\Controllers\Controller;
use Modules\Courses\Http\Requests\UserRoundsRequest;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\StudentView;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Services\RoundCompletionService;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Models\Student;

// Include the SMS file from Authentication module
require_once base_path('Modules/Authentication/smsfile.php');

class UserRoundsController extends Controller
{
    public function __construct(private RoundCompletionService $completion)
    {
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

    public function userCourses(Request $request)
    {
        try {
            $student = $request->user();

            // Get active user rounds
            $userRounds = UserRounds::where('student_id', $student->id)
                ->where('status', 'active')
                ->where('end_date', '>', now()->toDateString())
                ->pluck('round_id');

            // Get rounds directly
            $rounds = Rounds::whereIn('id', $userRounds)
                ->where('source', '0')
                ->withCount('round_contents')
                ->withCount('userRounds')
                ->get();

            if ($rounds->isEmpty()) {
                return res_data([], 'success', 200);
            }

            // Map rounds with exam-based achievement rate
            $roundsData = $rounds->map(function ($round) use ($student) {
                $achievementRate = $this->completion->totalPercentage($round->id, $student->id);

                return [
                    'id' => $round->id,
                    'name' => $round->name,
                    'image' => $round->image_url,
                    'achievement_rate' => $achievementRate,
                ];
            });

            // Calculate total achievement rate across all rounds
            $achievementRates = $roundsData->pluck('achievement_rate')->filter(function ($rate) {
                return $rate > 0;
            });

            $totalAchievementRate = $achievementRates->isNotEmpty()
                ? round($achievementRates->avg(), 2)
                : 0;

            $response = [
                'rounds' => $roundsData,
                'total_achievement_rate' => $totalAchievementRate . '%',
            ];

            return res_data($response, 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return res_data($e->errors(), 'Validation failed', 422);
        } catch (\Exception $e) {
            return res_data(null, 'Error: ' . $e->getMessage(), 500);
        }
    }

    public function getMyCompeletenessRoundByRoundID(Request $request)
    {
        try {

            $request->validate([
                'round_id' => 'required|integer'
            ]);


            $student = $request->user();
            $roundId = $request->round_id;


            if (!$roundId) {
                return res_data(null, 'Round ID is required', 400);
            }

            // Check if student is enrolled in this round
            $userRound = UserRounds::where('student_id', $student->id)
                ->where('round_id', $roundId)
                ->where('status', 'active')
                ->first();


            if (!$userRound) {
                return res_data(null, 'You are not enrolled in this round', 404);
            }

            $slots = $this->completion->examSlots((int) $roundId);
            $best = $this->completion->bestScores([$student->id], array_column($slots, 'exam_id'));
            $completion = $this->completion->breakdown($slots, $best[$student->id] ?? []);

            $basicContents = $completion['items']['basic'];
            $lectureContents = $completion['items']['lecture'];
            $fullRoundContents = $completion['items']['full_round'];

            $response = [
                'round_id' => $roundId,
                'basic' => $basicContents,
                'lecture' => $lectureContents,
                'full_round' => $fullRoundContents,
                'basic_percentage' => $completion['basic_percentage'] . '%',
                'lecture_percentage' => $completion['lecture_percentage'] . '%',
                'full_round_percentage' => $completion['full_round_percentage'] . '%',
                'total_percentage' => $completion['total_percentage'] . '%',
                'total_completed_basic' => count(array_filter($basicContents, fn($i) => $i['highest_score'] !== "0%" && $i['highest_score'] !== 0)),
                'total_completed_lecture' => count(array_filter($lectureContents, fn($i) => $i['highest_score'] !== "0%" && $i['highest_score'] !== 0)),
                'total_completed_full_round' => count(array_filter($fullRoundContents, fn($i) => $i['highest_score'] !== "0%" && $i['highest_score'] !== 0)),
                'total_contents' => count($basicContents) + count($lectureContents) + count($fullRoundContents),
            ];

            return res_data($response, 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Exception', ['errors' => $e->errors()]);
            return res_data($e->errors(), 'Validation failed', 422);
        } catch (\Exception $e) {
            Log::error('Exception in getMyCompeletenessRoundByRoundID', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return res_data(null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    public function enrollInCourse(UserRoundsRequest $request)
    {

        $data = $request->validated();
        $studentRound = UserRounds::where('student_id', $data['student_id'])->where('round_id', $data['round_id'])->first();
        if ($studentRound) {
            return res_data('لقد انضمت  لهذه الدوره من قبل', 'error', 400);
        }
        $studentRound = UserRounds::create([
            'student_id' => $data['student_id'],
            'round_id' => $data['round_id'],
            'status' => 'active',
            'end_date' => now()->addYears(1),
            'day' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'payment_id' => null,
        ]);
        if (!$studentRound) {
            return res_data('فشل إنضمامك لهذه الدورة', 'error', 400);
        }

        // Send congratulatory SMS
        try {
            $student = Student::find($data['student_id']);
            $round = Rounds::find($data['round_id']);
            
            if ($student && $round) {
                $studentName = $student->name;
                $roundName = $round->name;
                $phone = $student->phone;

                $smsMessage = "تم تفعيل اشتراكك في دورة: $roundName .. لمعرفة كيفية متابعة الدورة من خلال المنصة شاهد الفيديو التعريفي https://www.youtube.com/watch?v=dqwkvk7JU_I";
                
                sendWawpMessage($phone, $smsMessage);
            }
        } catch (\Exception $e) {
            Log::error('Enrollment SMS failed: ' . $e->getMessage());
        }

        return res_data($studentRound, 'success', 200);
    }

    // Helper function to get scores for round exams
    private function getScoresForRoundExams($roundId, $studentId)
    {
        $assignedExams = AssignExamModel::where('type', 'full_round')
            ->where('lesson_or_round_id', $roundId)
            ->get();

        if ($assignedExams->isEmpty()) {
            return [];
        }

        $examIds = $assignedExams->pluck('exam_id')->unique();

        $maxScores = StudentScoreModel::where('student_id', $studentId)
            ->whereIn('exam_id', $examIds)
            ->selectRaw('exam_id, MAX(score) as max_score')
            ->groupBy('exam_id')
            ->get()
            ->keyBy('exam_id');

        if ($maxScores->isEmpty()) {
            return [];
        }

        $studentScores = StudentScoreModel::where('student_id', $studentId)
            ->whereIn('exam_id', $examIds)
            ->get()
            ->filter(function ($score) use ($maxScores) {
                return isset($maxScores[$score->exam_id]) &&
                    $score->score == $maxScores[$score->exam_id]->max_score;
            })
            ->unique('exam_id')
            ->load(['exam' => function ($query) {
                $query->select('id', 'title', 'description', 'time');
            }]);

        return $studentScores->map(function ($score) {
            return [
                'score_id' => $score->id,
                'exam_id' => $score->exam_id,
                'exam_title' => $score->exam->title ?? null,
                'exam_description' => $score->exam->description ?? null,
                'exam_time' => $score->exam->time ?? null,
                'student_score' => $score->score,
                'scored_at' => $score->created_at,
            ];
        })->values()->toArray();
    }

    // Helper function to calculate average score
    private function calculateAverageScore($scores)
    {
        if (empty($scores)) {
            return 0;
        }

        $totalPercentage = 0;
        $count = 0;

        foreach ($scores as $score) {
            $scoreValue = $score['student_score'];

            // Parse score like "2/4" or "4/4"
            if (strpos($scoreValue, '/') !== false) {
                list($obtained, $total) = explode('/', $scoreValue);
                $obtained = (float) trim($obtained);
                $total = (float) trim($total);

                if ($total > 0) {
                    $percentage = ($obtained / $total) * 100;
                    $totalPercentage += $percentage;
                    $count++;
                }
            }
        }

        if ($count === 0) {
            return 0;
        }

        return round($totalPercentage / $count, 2);
    }
}
