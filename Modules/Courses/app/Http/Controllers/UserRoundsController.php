<?php

namespace Modules\Courses\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Courses\Models\Rounds;
use App\Http\Controllers\Controller;
use Modules\Courses\Http\Requests\UserRoundsRequest;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\StudentView;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Models\ExamModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\StudentScoreModel;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Models\Student;

// Include the SMS file from Authentication module
require_once base_path('Modules/Authentication/smsfile.php');

class UserRoundsController extends Controller
{
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
                // Calculate achievement rate based on exams (same logic as getRoundContentExamScore)
                $achievementRate = $this->calculateRoundAchievementRate($round->id, $student->id);

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

            // Get all round contents for this round with type 'basic' or 'lecture'
            $roundContents = RoundContetModel::where('round_id', $roundId)
                ->whereIn('type', ['basic', 'lecture'])
                ->orderBy('sort_number', 'asc')
                ->get();

            $contentIds = $roundContents->pluck('id');
            // Optimizing: Fetch all lessons and assigned exams in bulk
            $allLessons = LessonsModel::whereIn('round_content_id', $contentIds)->get()->groupBy('round_content_id');
            $lessonIds = $allLessons->flatten()->pluck('id');
            $allAssignedExams = AssignExamModel::where('type', 'lesson')
                ->whereIn('lesson_or_round_id', $lessonIds)
                ->get()
                ->groupBy('lesson_or_round_id');

            $examIds = $allAssignedExams->flatten()->pluck('exam_id')->unique();
            $exams = ExamModel::whereIn('id', $examIds)->get()->keyBy('id');

            // Pre-fetch all scores for the round contents to avoid multiple DB overhead
            $allScoresByContent = $this->getScoresForContents($contentIds, $student->id);
            $scoresByExam = collect($allScoresByContent)->keyBy('exam_id');


            $basicContents = [];
            $lectureContents = [];

            foreach ($roundContents as $content) {
                // Get lessons related to this content
                $lessons = $allLessons->get($content->id, []);

                foreach ($lessons as $lesson) {
                    // Get all assigned exams for this lesson
                    $assignedExams = $allAssignedExams->get($lesson->id, []);

                    foreach ($assignedExams as $assignedExam) {
                        $exam = $exams->get($assignedExam->exam_id);

                        if (!$exam) {
                            continue;
                        }

                        $highestScore = "0%";
                        $highestScoreDate = null;
                        $isSolved = false;

                        // Check if student has completed this specific exam
                        $bestScoreRecord = $scoresByExam->get($exam->id);
                        if ($bestScoreRecord) {
                            $isSolved = true;
                            $maxScore = $bestScoreRecord['student_score'];

                            // Convert score to percentage if it's in format "x/y"
                            if (strpos($maxScore, '/') !== false) {
                                list($obtained, $total) = explode('/', $maxScore);
                                $obtained = (float) trim($obtained);
                                $total = (float) trim($total);
                                if ($total > 0) {
                                    $maxScore = ($obtained / $total) * 100;
                                }
                            }
                            $highestScore = round($maxScore, 2) . '%';

                            // Format the date
                            $date = $bestScoreRecord['scored_at'];
                            if ($date instanceof \Carbon\Carbon) {
                                $highestScoreDate = $date->format('Y-m-d H:i:s');
                            } else {
                                $highestScoreDate = \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
                            }
                        }

                        $item = [
                            'content_id' => $content->id,
                            'lesson_id' => $lesson->id,
                            'exam_id' => $exam->id,
                            'exam_name' => $exam->title,
                            'type' => $content->type,
                            'highest_score' => $highestScore,
                            'created_at' => $highestScoreDate,
                            'solved' => $isSolved,
                        ];

                        if ($content->type == 'basic') {
                            $basicContents[] = $item;
                        } elseif ($content->type == 'lecture') {
                            $lectureContents[] = $item;
                        }
                    }
                }
            }

            // Process Full Round Exams
            $fullRoundContents = [];
            $assignedRoundExams = AssignExamModel::where('type', 'full_round')
                ->where('lesson_or_round_id', $roundId)
                ->get();


            foreach ($assignedRoundExams as $assignedExam) {
                $exam = ExamModel::find($assignedExam->exam_id);
                if (!$exam) continue;

                $scores = StudentScoreModel::where('student_id', $student->id)
                    ->where('exam_id', $exam->id)
                    ->get();

                $highestScore = "0%";
                $highestScoreDate = null;
                $isSolved = false;

                if ($scores->isNotEmpty()) {
                    $isSolved = true;
                    $maxScoreVal = $scores->max('score');
                    // Find record with max score
                    $bestScoreRecord = $scores->where('score', $maxScoreVal)->first();

                    $maxScore = $maxScoreVal;

                    // Convert score to percentage if it's in format "x/y"
                    if (strpos($maxScore, '/') !== false) {
                        list($obtained, $total) = explode('/', $maxScore);
                        $obtained = (float) trim($obtained);
                        $total = (float) trim($total);
                        if ($total > 0) {
                            $maxScore = ($obtained / $total) * 100;
                        }
                    }
                    $highestScore = round($maxScore, 2) . '%';

                    if ($bestScoreRecord) {
                        $date = $bestScoreRecord->created_at; // StudentScoreModel usually has created_at
                        if ($date instanceof \Carbon\Carbon) {
                            $highestScoreDate = $date->format('Y-m-d H:i:s');
                        } else {
                            $highestScoreDate = \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
                        }
                    }
                }

                $fullRoundContents[] = [
                    'content_id' => null,
                    'lesson_id' => null,
                    'exam_id' => $exam->id,
                    'exam_name' => $exam->title,
                    'type' => 'full_round',
                    'highest_score' => $highestScore,
                    'created_at' => $highestScoreDate,
                    'solved' => $isSolved,
                ];
            }


            // Helper closure to calculate average percentage from contents array
            $calculateAverage = function ($contents) {
                if (empty($contents)) return 0;
                $sum = 0;
                foreach ($contents as $item) {
                    $score = floatval(str_replace('%', '', $item['highest_score']));
                    $sum += $score;
                }
                return round($sum / count($contents), 2);
            };

            $basicPercentage = $calculateAverage($basicContents);
            $lecturePercentage = $calculateAverage($lectureContents);
            $fullRoundPercentage = $calculateAverage($fullRoundContents);

            // Calculate total average (similar to calculateRoundAchievementRate logic)
            $categoriesWithScores = [];
            if (!empty($basicContents)) $categoriesWithScores[] = $basicPercentage;
            if (!empty($lectureContents)) $categoriesWithScores[] = $lecturePercentage;
            if (!empty($fullRoundContents)) $categoriesWithScores[] = $fullRoundPercentage;

            $totalPercentage = count($categoriesWithScores) > 0
                ? round(array_sum($categoriesWithScores) / count($categoriesWithScores), 2)
                : 0;

            $response = [
                'round_id' => $roundId,
                'basic' => $basicContents,
                'lecture' => $lectureContents,
                'full_round' => $fullRoundContents,
                'basic_percentage' => $basicPercentage . '%',
                'lecture_percentage' => $lecturePercentage . '%',
                'full_round_percentage' => $fullRoundPercentage . '%',
                'total_percentage' => $totalPercentage . '%',
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
    private function calculateRoundAchievementRate($roundId, $studentId)
    {
        // 1. Get Contents
        $roundContents = RoundContetModel::where('round_id', $roundId)
            ->whereIn('type', ['basic', 'lecture'])
            ->orderBy('sort_number', 'asc')
            ->get();

        $basicScoresList = [];
        $lectureScoresList = [];

        // Pre-fetching for optimization is good, but to ensure EXACT match with "first()" logic
        // of the reference method without ambiguity, we will use the exact queries or 
        // extremely careful collection filtering.
        // Given the request for "logical exactness", let's replicate the structure.

        // Helper to get score for a list of exam IDs (Logic from getScoresForContents)
        $getHighestScoreForExams = function ($examIds) use ($studentId) {
            if (empty($examIds)) return 0;

            $scores = StudentScoreModel::where('student_id', $studentId)
                ->whereIn('exam_id', $examIds)
                ->get();

            if ($scores->isEmpty()) return 0;

            // Calculate max percentage
            $maxPct = 0;
            foreach ($scores as $s) {
                $val = $s->score;
                $pct = 0;
                if (strpos($val, '/') !== false) {
                    list($obtained, $total) = explode('/', $val);
                    $obtained = (float) trim($obtained);
                    $total = (float) trim($total);
                    if ($total > 0) $pct = ($obtained / $total) * 100;
                } else {
                    $pct = (float)$val;
                }
                if ($pct > $maxPct) $maxPct = $pct;
            }
            return $maxPct; // Float
        };

        foreach ($roundContents as $content) {
            $lessons = LessonsModel::where('round_content_id', $content->id)->get();
            foreach ($lessons as $lesson) {
                $assignedExams = AssignExamModel::where('type', 'lesson')
                    ->where('lesson_or_round_id', $lesson->id)
                    ->get();
                
                foreach ($assignedExams as $assigned) {
                    $exam = ExamModel::find($assigned->exam_id);
                    if (!$exam) continue;

                    $maxScoreVal = $getHighestScoreForExams([$exam->id]);
                    $roundedScore = round($maxScoreVal, 2);

                    if ($content->type == 'basic') {
                        $basicScoresList[] = $roundedScore;
                    } elseif ($content->type == 'lecture') {
                        $lectureScoresList[] = $roundedScore;
                    }
                }
            }
        }

        // Process Full Round Exams
        $fullRoundScoresList = [];
        $assignedRoundExams = AssignExamModel::where('type', 'full_round')
            ->where('lesson_or_round_id', $roundId)
            ->get();

        foreach ($assignedRoundExams as $assignedExam) {
            $exam = ExamModel::find($assignedExam->exam_id);
            if (!$exam) continue;

            $maxScoreVal = $getHighestScoreForExams([$exam->id]);
            $roundedScore = round($maxScoreVal, 2);
            $fullRoundScoresList[] = $roundedScore;
        }

        // Calculate Averages
        $categoriesWithScores = [];

        // Basic Average
        if (!empty($basicScoresList)) {
            $sum = array_sum($basicScoresList);
            $avg = $sum / count($basicScoresList);
            $categoriesWithScores[] = round($avg, 2);
        }

        // Lecture Average
        if (!empty($lectureScoresList)) {
            $sum = array_sum($lectureScoresList);
            $avg = $sum / count($lectureScoresList);
            $categoriesWithScores[] = round($avg, 2);
        }

        // Full Round Average
        if (!empty($fullRoundScoresList)) {
            $sum = array_sum($fullRoundScoresList);
            $avg = $sum / count($fullRoundScoresList);
            $categoriesWithScores[] = round($avg, 2);
        }

        // Total Average
        if (empty($categoriesWithScores)) return 0;

        $totalSum = array_sum($categoriesWithScores);
        $totalAvg = $totalSum / count($categoriesWithScores);

        return round($totalAvg, 2);
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

                $smsMessage = "أهلاً بك يا $studentName\nتهانينا! لقد تم اشتراكك بنجاح في دورة: $roundName\nنتمنى لك رحلة تعليمية ممتعة ومفيدة مع منصة نرتقي.";
                
                sendWawpMessage($phone, $smsMessage);
            }
        } catch (\Exception $e) {
            Log::error('Enrollment SMS failed: ' . $e->getMessage());
        }

        return res_data($studentRound, 'success', 200);
    }

    // Helper function to get scores for specific contents
    private function getScoresForContents($contentIds, $studentId)
    {
        if ($contentIds->isEmpty()) {
            return [];
        }

        $lessons = LessonsModel::whereIn('round_content_id', $contentIds)->pluck('id');

        if ($lessons->isEmpty()) {
            return [];
        }

        $assignedExams = AssignExamModel::where('type', 'lesson')
            ->whereIn('lesson_or_round_id', $lessons)
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
