<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Http\Requests\StoreStudentAnswerRequest;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\StudentAnswerModel;
use Modules\Courses\Models\StudentMockExamScratchModel;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Models\ExamSectionsModel;
use Modules\Courses\Models\AdminExamModel;

class StudentAnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function storeStudentAnswers(StoreStudentAnswerRequest $request)
    {
        $data = $request->validated();

        $studentId = $data['student_id'];
        $examId    = $data['exam_id'];
        $answers   = $data['answers'] ?? [];

        if (empty($answers)) {
            StudentAnswerModel::where('student_id', $studentId)->where('exam_id', $examId)->delete();
            return res_data('success', 'لم يتم ارسال اجابات', 200);
        }

        $rows = collect($answers)->map(function ($answer) use ($studentId, $examId) {
            return [
                'student_id'     => $studentId,
                'exam_id'        => $examId,
                'question_id'    => $answer['question_id'],
                'type'           => $answer['type'],
                'student_answer' => $answer['student_answer'],
                'correct_answer' => $answer['correct_answer'],
                'is_correct'     => $answer['is_correct'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        })->toArray();

        // Wrapped in a transaction so a failed insert rolls back the delete too,
        // and the mock exam scratch cleanup only ever happens after a real, successful submit.
        DB::transaction(function () use ($studentId, $examId, $rows) {
            StudentAnswerModel::where('student_id', $studentId)->where('exam_id', $examId)->delete();
            StudentAnswerModel::insert($rows);

            // Mock exam scratch pads are scoped to the attempt; once answers are
            // finally submitted for this exam, the scratches no longer apply.
            // No-op for normal/folder exams, which never have scratch rows.
            StudentMockExamScratchModel::where('student_id', $studentId)
                ->where('exam_id', $examId)
                ->delete();
        });

        return res_data($rows, 'تم إضافة الإجابات بنجاح', 200);
    }
    public function StoreStudentScore(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'exam_id' => 'required|exists:exams,id',
            'score' => 'required|string',
        ]);
        $studentScore = StudentScoreModel::create($data);
        if (!$studentScore) {
            return res_data(null, 'فشل إضافة النقاط', 400);
        }
        return res_data($studentScore, 'تم إضافة النقاط بنجاح', 200);
    }

    public function getStudentQuestionsWithAnswersByExamId(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
        ]);



        // Get all exam sections for this exam
        $sections = ExamSectionsModel::where('exam_id', $data['exam_id'])->get();

        if ($sections->isEmpty()) {
            return res_data(null, 'لا توجد أسئلة لهذا الامتحان', 400);
        }

        $sectionIds = $sections->pluck('id');

        // Fetch questions by exam_section_id with their options and paragraph
        $questions = AdminQuestionsModel::whereIn('exam_section_id', $sectionIds)
            ->with(['options', 'paragraph'])
            ->get();

        if ($questions->isEmpty()) {
            return res_data(null, 'لا توجد أسئلة لهذا الامتحان', 400);
        }

        // Fetch student answers for these questions
        $studentAnswers = StudentAnswerModel::where('exam_id', $data['exam_id'])
            ->where('student_id', $data['student_id'])
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        // Build sections structure similar to get_exam_sectionsWithQuestions,
        // but enriched with student's answer and correctness flags.
        $examLevel = AdminExamModel::where('id', $data['exam_id'])->value('level');

        $result = $sections->map(function ($section) use ($questions, $studentAnswers, $examLevel) {
            $sectionQuestions = $questions->where('exam_section_id', $section->id);

            $formattedQuestions = [];
            $processedParagraphIds = [];

            foreach ($sectionQuestions as $question) {
                if ($question->question_type === 'paragraph_mcq') {
                    $pId = $question->paragraph_id;
                    if (in_array($pId, $processedParagraphIds)) {
                        continue;
                    }
                    $processedParagraphIds[] = $pId;

                    $paragraphQuestions = $sectionQuestions->where('paragraph_id', $pId);
                    $paragraphModel = $question->paragraph;

                    $formattedQuestions[] = [
                        'id' => $paragraphModel ? $paragraphModel->id : $pId,
                        'question_type' => 'paragraph',
                        'paragraph' => $paragraphModel ? [
                            'id'                => $paragraphModel->id,
                            'exam_section_id'   => $paragraphModel->exam_section_id,
                            'paragraph_content' => $paragraphModel->paragraph_content,
                            'voice'             => $paragraphModel->voice,
                            'description'       => $paragraphModel->description,
                        ] : null,
                        'questions' => $paragraphQuestions->map(function ($q) use ($studentAnswers) {
                            $studentAnswer = $studentAnswers->get($q->id);
                            $correctOption = $q->options->firstWhere('is_correct', 1);

                            $options = $q->options->map(function ($option) {
                                return [
                                    'id'                  => $option->id,
                                    'option_text'         => $option->option_text,
                                    'is_correct'          => (bool) $option->is_correct,
                                    'question_explanation' => $option->question_explanation,
                                ];
                            })->values();

                            return [
                                'id'              => $q->id,
                                'exam_section_id' => $q->exam_section_id,
                                'question_text'   => $q->question_text,
                                'question_type'   => $q->question_type,
                                'instructions'    => $q->instructions,
                                'paragraph_id'    => $q->paragraph_id,
                                'options'         => $options,
                                'correct_option_id' => $correctOption?->id,
                                'student_answer'    => $studentAnswer->student_answer ?? null,
                                'is_solved'         => $studentAnswer ? (bool) $studentAnswer->is_correct : false,
                            ];
                        })->values()->toArray(),
                    ];
                } else {
                    $studentAnswer = $studentAnswers->get($question->id);
                    $correctOption = $question->options->firstWhere('is_correct', 1);

                    $options = $question->options->map(function ($option) {
                        return [
                            'id'                  => $option->id,
                            'option_text'         => $option->option_text,
                            'is_correct'          => (bool) $option->is_correct,
                            'question_explanation' => $option->question_explanation,
                        ];
                    })->values();

                    $formattedQuestions[] = [
                        'id'              => $question->id,
                        'exam_section_id' => $question->exam_section_id,
                        'question_text'   => $question->question_text,
                        'question_type'   => $question->question_type,
                        'instructions'    => $question->instructions,
                        'paragraph_id'    => null,
                        'options'         => $options,
                        'correct_option_id' => $correctOption?->id,
                        'student_answer'    => $studentAnswer->student_answer ?? null,
                        'is_solved'         => $studentAnswer ? (bool) $studentAnswer->is_correct : false,
                        'paragraph'       => null,
                    ];
                }
            }

            return [
                'id'           => $section->id,
                'exam_id'      => $section->exam_id,
                'title'        => $section->title,
                'description'  => $section->description,
                'time_if_free' => $section->time_if_free,
                'level'        => $examLevel,
                'questions'    => $formattedQuestions,
            ];
        });


        // Overall solved flag for this exam & student
        $check_solved_exam = StudentScoreModel::where('exam_id', $data['exam_id'])
            ->where('student_id', $data['student_id'])
            ->exists();
        $lastStudentScore = StudentScoreModel::where('exam_id', $data['exam_id'])
            ->where('student_id', $data['student_id'])
            ->orderBy('id', 'desc')
            ->first();

        return res_data([
            'sections'  => $result,
            'is_solved' => $check_solved_exam,
            'lastStudentScore' => $lastStudentScore,
        ], 'تم جلب الأسئلة بنجاح', 200);
    }

    /**
     * Get comprehensive exam submission summary report & analytics.
     * Matches the performance report UI (Overall score, difficulty breakdown, section details, question answers).
     */
    public function getExamSummaryReport(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $studentId = $data['student_id'] ?? $request->user()?->id;

        if (!$studentId) {
            return res_data(null, 'معرف الطالب مطلوب (student_id في Body أو Token في الهيدر)', 400);
        }

        // Fetch Exam details
        $exam = AdminExamModel::with(['examLabel'])->find($data['exam_id']);
        if (!$exam) {
            return res_data(null, 'الامتحان غير موجود', 404);
        }

        // Fetch all exam sections for this exam
        $sections = ExamSectionsModel::where('exam_id', $exam->id)->get();
        $sectionIds = $sections->pluck('id');

        // Fetch questions with options and paragraphs
        $questions = AdminQuestionsModel::whereIn('exam_section_id', $sectionIds)
            ->with(['options', 'paragraph'])
            ->get();

        // Fetch student answers
        $studentAnswers = StudentAnswerModel::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('question_id');

        // Fetch overall performance stats for all questions across ALL students to dynamically determine difficulty
        $overallQuestionStats = StudentAnswerModel::whereIn('question_id', $questions->pluck('id'))
            ->select(
                'question_id',
                DB::raw('COUNT(*) as total_answers'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers')
            )
            ->groupBy('question_id')
            ->get()
            ->keyBy('question_id');

        // Fetch latest student score record if exists
        $studentScoreRecord = StudentScoreModel::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderBy('id', 'desc')
            ->first();

        // Global counters
        $totalQuestionsCount = $questions->count();
        $correctAnswersCount = 0;
        $wrongAnswersCount = 0;
        $unansweredCount = 0;

        // Difficulty level counters
        $difficultyStats = [
            'easy' => [
                'level_key' => 'easy',
                'level_name' => 'مستوى سهل',
                'correct_count' => 0,
                'wrong_count' => 0,
                'unanswered_count' => 0,
                'total_count' => 0,
            ],
            'medium' => [
                'level_key' => 'medium',
                'level_name' => 'مستوى متوسط',
                'correct_count' => 0,
                'wrong_count' => 0,
                'unanswered_count' => 0,
                'total_count' => 0,
            ],
            'hard' => [
                'level_key' => 'hard',
                'level_name' => 'مستوى صعب',
                'correct_count' => 0,
                'wrong_count' => 0,
                'unanswered_count' => 0,
                'total_count' => 0,
            ],
            'unspecified' => [
                'level_key' => 'unspecified',
                'level_name' => 'مستوى غير محدد',
                'correct_count' => 0,
                'wrong_count' => 0,
                'unanswered_count' => 0,
                'total_count' => 0,
            ],
        ];

        // Process sections and questions
        $sectionsPerformance = [];
        $globalQuestionCounter = 0;

        $numberWords = [
            1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس',
            6 => 'السادس', 7 => 'السابع', 8 => 'الثامن', 9 => 'التاسع', 10 => 'العاشر',
            11 => 'الحادي عشر', 12 => 'الثاني عشر', 13 => 'الثالث عشر', 14 => 'الرابع عشر', 15 => 'الخامس عشر',
            16 => 'السادس عشر', 17 => 'السابع عشر', 18 => 'الثامن عشر', 19 => 'التاسع عشر', 20 => 'العشرون'
        ];

        foreach ($sections as $section) {
            $sectionQuestions = $questions->where('exam_section_id', $section->id);
            $secCorrect = 0;
            $secWrong = 0;
            $secUnanswered = 0;
            $secTotal = $sectionQuestions->count();
            $formattedQuestions = [];

            foreach ($sectionQuestions as $q) {
                $globalQuestionCounter++;
                $studentAnswer = $studentAnswers->get($q->id);

                // Determine question status
                if (!$studentAnswer) {
                    $status = 'unanswered';
                    $statusLabel = 'غير مجاب';
                    $isCorrect = null;
                    $unansweredCount++;
                    $secUnanswered++;
                } else if ($studentAnswer->is_correct) {
                    $status = 'correct';
                    $statusLabel = 'إجابة صحيحة';
                    $isCorrect = true;
                    $correctAnswersCount++;
                    $secCorrect++;
                } else {
                    $status = 'wrong';
                    $statusLabel = 'إجابة خاطئة';
                    $isCorrect = false;
                    $wrongAnswersCount++;
                    $secWrong++;
                }

                // Dynamically determine difficulty level based on overall student answers for this question
                $questionStat = $overallQuestionStats->get($q->id);
                if ($questionStat && $questionStat->total_answers > 0) {
                    $pctCorrect = ($questionStat->correct_answers / $questionStat->total_answers) * 100;
                    if ($pctCorrect >= 65) {
                        $diffKey = 'easy'; // المعظم أجاب بشكل صحيح (سهل)
                    } else if ($pctCorrect <= 40) {
                        $diffKey = 'hard'; // المعظم أجاب بشكل خاطئ (صعب)
                    } else {
                        $diffKey = 'medium'; // الأجوبة متوسطة (متوسط)
                    }
                } else {
                    // Fallback to static level on question/section if no answers recorded yet
                    $rawLevel = $q->level ?? $section->level ?? 'unspecified';
                    $diffKey = match (strtolower((string)$rawLevel)) {
                        'easy', 'سهل' => 'easy',
                        'medium', 'متوسط' => 'medium',
                        'hard', 'صعب' => 'hard',
                        default => 'unspecified',
                    };
                }

                $diffLabel = match ($diffKey) {
                    'easy' => 'مستوى سهل',
                    'medium' => 'مستوى متوسط',
                    'hard' => 'مستوى صعب',
                    default => 'نوع غير محدد',
                };

                // Update difficulty stats
                $difficultyStats[$diffKey]['total_count']++;
                if ($status === 'correct') {
                    $difficultyStats[$diffKey]['correct_count']++;
                } else if ($status === 'wrong') {
                    $difficultyStats[$diffKey]['wrong_count']++;
                } else {
                    $difficultyStats[$diffKey]['unanswered_count']++;
                }

                // Correct option & explanation
                $correctOption = $q->options->firstWhere('is_correct', 1);
                $questionExplanation = $correctOption?->question_explanation ?? $q->description ?? '';

                $numWord = $numberWords[$globalQuestionCounter] ?? "رقم {$globalQuestionCounter}";

                $formattedQuestions[] = [
                    'question_id' => $q->id,
                    'question_number_title' => "السؤال {$numWord}",
                    'global_number' => $globalQuestionCounter,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'is_correct' => $isCorrect,
                    'difficulty_level' => $diffKey,
                    'difficulty_label' => $diffLabel,
                    'student_answer' => $studentAnswer?->student_answer,
                    'correct_answer' => $studentAnswer?->correct_answer ?? $correctOption?->option_text,
                    'explanation' => $questionExplanation,
                    'paragraph' => $q->paragraph ? [
                        'id' => $q->paragraph->id,
                        'paragraph_content' => $q->paragraph->paragraph_content,
                        'description' => $q->paragraph->description,
                        'voice' => $q->paragraph->voice,
                    ] : null,
                    'options' => $q->options->map(function ($opt) {
                        return [
                            'id' => $opt->id,
                            'option_text' => $opt->option_text,
                            'is_correct' => (bool) $opt->is_correct,
                            'question_explanation' => $opt->question_explanation,
                        ];
                    })->values(),
                ];
            }

            $sectionsPerformance[] = [
                'section_id' => $section->id,
                'section_title' => $section->title,
                'section_description' => $section->description,
                'section_subtitle' => "{$secCorrect} صحيحة من {$secTotal}",
                'stats' => [
                    'correct_count' => $secCorrect,
                    'wrong_count' => $secWrong,
                    'unanswered_count' => $secUnanswered,
                    'total_questions' => $secTotal,
                ],
                'questions' => $formattedQuestions,
            ];
        }

        // Format difficulty levels output
        $formattedDifficulty = [];
        foreach ($difficultyStats as $key => $stat) {
            $lvlTotal = $stat['total_count'];
            $lvlCorrect = $stat['correct_count'];
            $pct = $lvlTotal > 0 ? round(($lvlCorrect / $lvlTotal) * 100, 2) : 0;

            $formattedDifficulty[$key] = [
                'level_key' => $key,
                'level_name' => $stat['level_name'],
                'percentage' => "{$pct}%",
                'percentage_number' => $pct,
                'subtitle' => "{$lvlCorrect} صحيح من {$lvlTotal}",
                'correct_count' => $stat['correct_count'],
                'wrong_count' => $stat['wrong_count'],
                'unanswered_count' => $stat['unanswered_count'],
                'total_count' => $lvlTotal,
            ];
        }

        // Calculate overall score percentage
        $scorePercentage = $totalQuestionsCount > 0
            ? round(($correctAnswersCount / $totalQuestionsCount) * 100, 2)
            : 0;

        // If student score record exists, use that if score string is present
        if ($studentScoreRecord && is_numeric(str_replace('%', '', $studentScoreRecord->score))) {
            $scorePercentage = (float) str_replace('%', '', $studentScoreRecord->score);
        }

        // Status recommendations based on percentage
        if ($scorePercentage >= 85) {
            $statusTitle = 'أداء ممتاز! استمر في التقدم';
            $statusDescription = 'نتيجة رائعة جداً، واصل التفوق والتدرب للحفاظ على مستواك العالي.';
        } else if ($scorePercentage >= 50) {
            $statusTitle = 'أداء جيد، يمكنك تحقيق الأفضل!';
            $statusDescription = 'نتيجة جيدة، يرجى مراجعة الأسئلة الخاطئة لتقوية نقاط الضعف.';
        } else {
            $statusTitle = 'راجع النقاط الأساسية ثم حاول مجدداً';
            $statusDescription = 'النتيجة ليست حكماً نهائياً، استخدم خريطة الأخطاء بالأسهل، وابدأ من الأسئلة السهلة.';
        }

        return res_data([
            'exam_info' => [
                'exam_id' => $exam->id,
                'exam_title' => $exam->title,
                'exam_label' => $exam->examLabel?->name ?? 'اختبار تدريبي',
                'level' => $exam->level,
                'success_percentage' => $exam->success_percentage,
            ],
            'overall_result' => [
                'score_percentage' => "{$scorePercentage}%",
                'score_percentage_number' => $scorePercentage,
                'status_title' => $statusTitle,
                'status_description' => $statusDescription,
                'total_questions' => $totalQuestionsCount,
                'correct_answers' => $correctAnswersCount,
                'wrong_answers' => $wrongAnswersCount,
                'unanswered' => $unansweredCount,
            ],
            'performance_by_difficulty' => $formattedDifficulty,
            'sections_performance' => $sectionsPerformance,
        ], 'تم جلب تقرير نتائج الاختبار بنجاح', 200);
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
