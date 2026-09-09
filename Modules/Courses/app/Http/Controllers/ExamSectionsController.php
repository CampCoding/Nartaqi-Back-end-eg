<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamSectionsModel;
use Modules\Courses\Models\AdminQuestionsModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Models\QuestionParagraphsModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\StudentAnswerModel;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\StudentMockExamScratchModel;
use Modules\Courses\Models\StudentQuestionFolderItemModel;

class ExamSectionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_exam_sectionsWithQuestions(Request $request)
    {
        try {
            $data = $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'student_id' => 'required|exists:students,id',
                'lesson_id' => 'required|exists:lessons,id',
            ]);

            $hasAccess = false;

            // 1. Check if the exam itself is free
            $examinfo_check = AdminExamModel::find($data['exam_id']);
            if ($examinfo_check && $examinfo_check->free == '1') {
                $hasAccess = true;
            }

            // 2. Check if context lesson's round is free or student is subscribed
            if (!$hasAccess) {
                $lesson = LessonsModel::find($data['lesson_id']);
                if ($lesson) {
                    $roundContent = RoundContetModel::find($lesson->round_content_id);
                    if ($roundContent) {
                        $isSubscribed = UserRounds::where('student_id', $data['student_id'])
                            ->where('round_id', $roundContent->round_id)
                            ->exists();
                        $isFree = Rounds::where('id', $roundContent->round_id)->where('free', '1')->exists();

                        if ($isSubscribed || $isFree) {
                            $hasAccess = true;
                        }
                    }
                }
            }

            // 3. Check if student has access to this exam through assignments in assign_exam_round table
            if (!$hasAccess) {
                $assignments = AssignExamModel::where('exam_id', $data['exam_id'])->get();
                foreach ($assignments as $assignment) {
                    $roundId = null;
                    if ($assignment->type === 'full_round') {
                        $roundId = $assignment->lesson_or_round_id;
                    } elseif ($assignment->type === 'lesson') {
                        $lesson_assignment = LessonsModel::find($assignment->lesson_or_round_id);
                        if ($lesson_assignment) {
                            $roundContent_assignment = RoundContetModel::find($lesson_assignment->round_content_id);
                            if ($roundContent_assignment) {
                                $roundId = $roundContent_assignment->round_id;
                            }
                        }
                    }

                    if ($roundId) {
                        $isSubscribed = UserRounds::where('student_id', $data['student_id'])
                            ->where('round_id', $roundId)
                            ->exists();
                        $isFree = Rounds::where('id', $roundId)->where('free', '1')->exists();

                        if ($isSubscribed || $isFree) {
                            $hasAccess = true;
                            break;
                        }
                    }
                }
            }

            // 4. Check if student has access to this exam through direct round_id/lesson_id in exams table
            if (!$hasAccess && $examinfo_check) {
                $roundId = null;
                if ($examinfo_check->round_id) {
                    $roundId = $examinfo_check->round_id;
                } elseif ($examinfo_check->lesson_id) {
                    $lesson_direct = LessonsModel::find($examinfo_check->lesson_id);
                    if ($lesson_direct) {
                        $roundContent_direct = RoundContetModel::find($lesson_direct->round_content_id);
                        if ($roundContent_direct) {
                            $roundId = $roundContent_direct->round_id;
                        }
                    }
                }

                if ($roundId) {
                    $isSubscribed = UserRounds::where('student_id', $data['student_id'])
                        ->where('round_id', $roundId)
                        ->exists();
                    $isFree = Rounds::where('id', $roundId)->where('free', '1')->exists();
                    if ($isSubscribed || $isFree) {
                        $hasAccess = true;
                    }
                }
            }

            if (! $hasAccess) {
                return res_data('no_access', 'لا يوجد إشتراك او صلاحية لهذا الطالب لهذا الامتحان', 400);
            }

            $sections = ExamSectionsModel::where('exam_id', $data['exam_id'])->orderBy('id', 'asc')->get();
            $examinfo = AdminExamModel::where('id', $data['exam_id'])->first();

            // Check if student solved the exam and get the latest score
            $studentScore = StudentScoreModel::where('exam_id', $data['exam_id'])
                ->where('student_id', $data['student_id'])
                ->orderBy('created_at', 'desc')
                ->first();

            $check_solved_exam = $studentScore ? true : false;

            // Extract numeric value from score (handles formats like "2/9" or "2")
            $lastScore = null;
            if ($studentScore && $studentScore->score) {
                $scoreValue = $studentScore->score;
                // Check if it's in format "2/9" - extract the first number
                if (strpos($scoreValue, '/') !== false) {
                    $parts = explode('/', $scoreValue);
                    $lastScore = is_numeric($parts[0]) ? (float)$parts[0] : null;
                } elseif (is_numeric($scoreValue)) {
                    $lastScore = (float)$scoreValue;
                }
            }

            // Map sections and include exam_videos/exam_pdfs from outer scope
            $result = $sections->map(function ($section) use ($data, $examinfo) {
                // Get all questions for this section
                $questions = AdminQuestionsModel::where('exam_section_id', $section->id)->with(['options', 'paragraph'])->orderBy('id', 'asc')->get();

                // Map question_id => list of folder names this student saved it in (empty if not saved)
                $folderNamesByQuestionId = StudentQuestionFolderItemModel::whereIn('question_id', $questions->pluck('id'))
                    ->whereHas('folder', function ($q) use ($data) {
                        $q->where('student_id', $data['student_id']);
                    })
                    ->with('folder')
                    ->get()
                    ->groupBy('question_id')
                    ->map(function ($items) {
                        return $items->map(function ($item) {
                            return [
                                'id' => $item->folder->id,
                                'name' => $item->folder->name,
                            ];
                        })->values()->toArray();
                    });

                $formattedQuestions = [];
                $processedParagraphIds = [];

                foreach ($questions as $question) {
                    if ($question->question_type === 'paragraph_mcq' && $question->paragraph_id !== null) {
                        $pId = $question->paragraph_id;
                        if (in_array($pId, $processedParagraphIds)) {
                            continue;
                        }
                        $processedParagraphIds[] = $pId;

                        $paragraphQuestions = $questions->where('paragraph_id', $pId);
                        $paragraphModel = $question->paragraph;

                        $formattedQuestions[] = [
                            'id' => $paragraphModel ? $paragraphModel->id : $pId,
                            'question_type' => 'paragraph',
                            'paragraph' => $paragraphModel ? [
                                'id' => $paragraphModel->id,
                                'exam_section_id' => $paragraphModel->exam_section_id,
                                'paragraph_content' => $paragraphModel->paragraph_content,
                                'voice' => $paragraphModel->voice,
                                'description' => $paragraphModel->description,
                            ] : null,
                            'questions' => $paragraphQuestions->map(function ($q) use ($data, $folderNamesByQuestionId) {
                                $student_answer = StudentAnswerModel::where('question_id', $q->id)
                                    ->where('student_id', $data['student_id'])
                                    ->where('exam_id', $data['exam_id'])
                                    ->first();

                                $student_answer_id = $student_answer->student_answer ?? null;
                                $correct_option = $q->options->firstWhere('is_correct', 1);
                                $correct_answer_id = $correct_option->id ?? null;
                                $is_correct = $student_answer->is_correct ?? false;

                                return [
                                    'id' => $q->id,
                                    'exam_section_id' => $q->exam_section_id,
                                    'question_text' => $q->question_text,
                                    'question_type' => $q->question_type,
                                    'instructions' => $q->instructions,
                                    'paragraph_id' => $q->paragraph_id,
                                    'options' => $q->options,
                                    'student_answer_id' => $student_answer_id,
                                    'student_answer' => $student_answer->student_answer ?? null,
                                    'correct_answer_id' => $correct_answer_id,
                                    'correct_answer' => $correct_option->option_text ?? null,
                                    'is_correct' => $is_correct ? true : false,
                                    'is_solved' => $student_answer ? true : false,
                                    'folders' => $folderNamesByQuestionId->get($q->id, []),
                                ];
                            })->values()->toArray(),
                        ];
                    } else {
                        $formattedQuestions[] = [
                            'id' => $question->id,
                            'exam_section_id' => $question->exam_section_id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'instructions' => $question->instructions,
                            'description' => $question->description,
                            'paragraph_id' => null,
                            'options' => $question->options,
                            'paragraph' => null,
                            'folders' => $folderNamesByQuestionId->get($question->id, []),
                        ];
                    }
                }

                return [
                    'id' => $section->id,
                    'exam_id' => $section->exam_id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'time_if_free' => $section->time_if_free,
                    'level' => $examinfo->level,
                    'questions' => $formattedQuestions,
                ];
            });

            // Calculate score statistics
            $correctAnswers = 0;
            $totalQuestions = 0;
            $scorePercentage = 0.00;
            $studentMaxScorePercentage = 0.00;

            // Get the highest score for this student in this exam
            $allScores = StudentScoreModel::where('exam_id', $data['exam_id'])
                ->where('student_id', $data['student_id'])
                ->get();

            $maxScore = null;
            $maxNumericScore = 0;

            foreach ($allScores as $scoreRecord) {
                if ($scoreRecord->score) {
                    $scoreValue = $scoreRecord->score;
                    $numericValue = null;

                    // Check if it's in format "2/9" - extract the first number
                    if (strpos($scoreValue, '/') !== false) {
                        $parts = explode('/', $scoreValue);
                        $numericValue = is_numeric($parts[0]) ? (float)$parts[0] : null;
                    } elseif (is_numeric($scoreValue)) {
                        $numericValue = (float)$scoreValue;
                    }

                    if ($numericValue !== null && $numericValue > $maxNumericScore) {
                        $maxNumericScore = $numericValue;
                        $maxScore = $numericValue;
                    }
                }
            }

            if ($check_solved_exam) {
                $totalQuestions = (int)AdminQuestionsModel::whereIn('exam_section_id', $sections->pluck('id'))->count();

                $correctAnswers = (int)StudentAnswerModel::where('exam_id', $data['exam_id'])
                    ->where('student_id', $data['student_id'])
                    ->where('is_correct', 1)
                    ->count();

                if ($totalQuestions > 0 && $lastScore !== null && is_numeric($lastScore)) {
                    $scorePercentage = round(((float)$lastScore / $totalQuestions) * 100, 2);
                }

                if ($totalQuestions > 0 && $maxScore !== null && is_numeric($maxScore)) {
                    $studentMaxScorePercentage = round(((float)$maxScore / $totalQuestions) * 100, 2);
                }
            }

            return res_data([
                'sections' => $result,
                'exam_info' => $examinfo,
                'round_id' => $examinfo->round_id ?? null,
                'is_solved' => $check_solved_exam,
                'last_score' => $lastScore,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'score_percentage' => $scorePercentage,
                'max_score_percentage' => 100.00,
                'student_max_score_percentage' => $studentMaxScorePercentage,
            ], 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessage = $e->validator->errors()->first();
            return res_data($errorMessage, 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'خطأ غير معروف';
            return res_data($errorMessage, 'حدث خطأ أثناء جلب الأقسام', 500);
        }
    }
    public function get_mock_exam_sectionsWithQuestions(Request $request)
    {
        try {
            $data = $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'student_id' => 'required|exists:students,id',
            ]);

            $hasAccess = false;
            $determinedRoundId = null;

            // 1. Check if the exam itself is free
            $examinfo_check = AdminExamModel::find($data['exam_id']);
            if ($examinfo_check && $examinfo_check->free == '1') {
                $hasAccess = true;
                $determinedRoundId = $examinfo_check->round_id;
            }

            // 2. Check if student has access to this exam through assignments in assign_exam_round table
            if (!$hasAccess) {
                $assignments = AssignExamModel::where('exam_id', $data['exam_id'])->get();
                foreach ($assignments as $assignment) {
                    $roundId = null;
                    if ($assignment->type === 'full_round') {
                        $roundId = $assignment->lesson_or_round_id;
                    } elseif ($assignment->type === 'lesson') {
                        $lesson = LessonsModel::find($assignment->lesson_or_round_id);
                        if ($lesson) {
                            $roundContent = RoundContetModel::find($lesson->round_content_id);
                            if ($roundContent) {
                                $roundId = $roundContent->round_id;
                            }
                        }
                    }

                    if ($roundId) {
                        $isSubscribed = UserRounds::where('student_id', $data['student_id'])
                            ->where('round_id', $roundId)
                            ->exists();
                        $isFree = Rounds::where('id', $roundId)->where('free', '1')->exists();

                        if ($isSubscribed || $isFree) {
                            $hasAccess = true;
                            $determinedRoundId = $roundId;
                            break;
                        }
                    }
                }
            }

            if (!$hasAccess && $examinfo_check) {
                $roundId = null;
                if ($examinfo_check->round_id) {
                    $roundId = $examinfo_check->round_id;
                } elseif ($examinfo_check->lesson_id) {
                    $lesson_direct = LessonsModel::find($examinfo_check->lesson_id);
                    if ($lesson_direct) {
                        $roundContent_direct = RoundContetModel::find($lesson_direct->round_content_id);
                        if ($roundContent_direct) {
                            $roundId = $roundContent_direct->round_id;
                        }
                    }
                }

                if ($roundId) {
                    $isSubscribed = UserRounds::where('student_id', $data['student_id'])
                        ->where('round_id', $roundId)
                        ->exists();
                    $isFree = Rounds::where('id', $roundId)->where('free', '1')->exists();
                    if ($isSubscribed || $isFree) {
                        $hasAccess = true;
                        $determinedRoundId = $roundId;
                    }
                }
            }

            if (! $hasAccess) {
                return res_data('no_access', 'لا يوجد إشتراك او صلاحية لهذا الطالب لهذا الامتحان', 400);
            }

            $sections = ExamSectionsModel::where('exam_id', $data['exam_id'])->orderBy('id', 'asc')->get();
            $examinfo = AdminExamModel::where('id', $data['exam_id'])->first();


            $previousExamId = null;
            $nextExamId = null;
            if ($determinedRoundId) {
                $examOrder = AssignExamModel::where('type', 'full_round')
                    ->where('lesson_or_round_id', $determinedRoundId)
                    ->orderBy('sort_number', 'asc')
                    ->pluck('exam_id')
                    ->unique()
                    ->values();

                $examTypesById = AdminExamModel::whereIn('id', $examOrder)->pluck('type', 'id');

                $currentIndex = $examOrder->search((int) $data['exam_id']);
                if ($currentIndex !== false) {
                    $previousExamId = $currentIndex > 0 ? [
                        'id' => $examOrder[$currentIndex - 1],
                        'type' => $examTypesById[$examOrder[$currentIndex - 1]] ?? null,
                    ] : null;
                    $nextExamId = $currentIndex < $examOrder->count() - 1 ? [
                        'id' => $examOrder[$currentIndex + 1],
                        'type' => $examTypesById[$examOrder[$currentIndex + 1]] ?? null,
                    ] : null;
                }
            }

            $studentScore = StudentScoreModel::where('exam_id', $data['exam_id'])
                ->where('student_id', $data['student_id'])
                ->orderBy('created_at', 'desc')
                ->first();

            $check_solved_exam = $studentScore ? true : false;

            $lastScore = null;
            if ($studentScore && $studentScore->score) {
                $scoreValue = $studentScore->score;
                if (strpos($scoreValue, '/') !== false) {
                    $parts = explode('/', $scoreValue);
                    $lastScore = is_numeric($parts[0]) ? (float)$parts[0] : null;
                } elseif (is_numeric($scoreValue)) {
                    $lastScore = (float)$scoreValue;
                }
            }

            // Fetched once for the whole exam (not per-question) to avoid N+1.
            $mockScratches = StudentMockExamScratchModel::where('student_id', $data['student_id'])
                ->where('exam_id', $data['exam_id'])
                ->get()
                ->keyBy('question_id');

            $result = $sections->map(function ($section) use ($data, $examinfo, $mockScratches) {
                $questions = AdminQuestionsModel::where('exam_section_id', $section->id)->with(['options', 'paragraph'])->orderBy('id', 'asc')->get();

                // Map question_id => list of folder names this student saved it in (empty if not saved)
                $folderNamesByQuestionId = StudentQuestionFolderItemModel::whereIn('question_id', $questions->pluck('id'))
                    ->whereHas('folder', function ($q) use ($data) {
                        $q->where('student_id', $data['student_id']);
                    })
                    ->with('folder')
                    ->get()
                    ->groupBy('question_id')
                    ->map(function ($items) {
                        return $items->map(function ($item) {
                            return [
                                'id' => $item->folder->id,
                                'name' => $item->folder->name,
                            ];
                        })->values()->toArray();
                    });

                $formattedQuestions = [];
                $processedParagraphIds = [];

                foreach ($questions as $question) {
                    if ($question->question_type === 'paragraph_mcq' && $question->paragraph_id !== null) {
                        $pId = $question->paragraph_id;
                        if (in_array($pId, $processedParagraphIds)) {
                            continue;
                        }
                        $processedParagraphIds[] = $pId;

                        $paragraphQuestions = $questions->where('paragraph_id', $pId);
                        $paragraphModel = $question->paragraph;

                        $formattedQuestions[] = [
                            'id' => $paragraphModel ? $paragraphModel->id : $pId,
                            'question_type' => 'paragraph',
                            'paragraph' => $paragraphModel ? [
                                'id' => $paragraphModel->id,
                                'exam_section_id' => $paragraphModel->exam_section_id,
                                'paragraph_content' => $paragraphModel->paragraph_content,
                                'voice' => $paragraphModel->voice,
                                'description' => $paragraphModel->description,
                            ] : null,
                            'questions' => $paragraphQuestions->map(function ($q) use ($data, $mockScratches, $folderNamesByQuestionId) {
                                $student_answer = StudentAnswerModel::where('question_id', $q->id)
                                    ->where('student_id', $data['student_id'])
                                    ->where('exam_id', $data['exam_id'])
                                    ->first();

                                $student_answer_id = $student_answer->student_answer ?? null;
                                $correct_option = $q->options->firstWhere('is_correct', 1);
                                $correct_answer_id = $correct_option->id ?? null;
                                $is_correct = $student_answer->is_correct ?? false;

                                return [
                                    'id' => $q->id,
                                    'exam_section_id' => $q->exam_section_id,
                                    'question_text' => $q->question_text,
                                    'question_type' => $q->question_type,
                                    'instructions' => $q->instructions,
                                    'paragraph_id' => $q->paragraph_id,
                                    'options' => $q->options,
                                    'student_answer_id' => $student_answer_id,
                                    'student_answer' => $student_answer->student_answer ?? null,
                                    'correct_answer_id' => $correct_answer_id,
                                    'correct_answer' => $correct_option->option_text ?? null,
                                    'is_correct' => $is_correct ? true : false,
                                    'is_solved' => $student_answer ? true : false,
                                    'scratch_data' => $mockScratches->get($q->id)->scratch_data ?? null,
                                    'folders' => $folderNamesByQuestionId->get($q->id, []),
                                ];
                            })->values()->toArray(),
                        ];
                    } else {
                        $formattedQuestions[] = [
                            'id' => $question->id,
                            'exam_section_id' => $question->exam_section_id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'instructions' => $question->instructions,
                            'description' => $question->description,
                            'paragraph_id' => null,
                            'options' => $question->options,
                            'paragraph' => null,
                            'scratch_data' => $mockScratches->get($question->id)->scratch_data ?? null,
                            'folders' => $folderNamesByQuestionId->get($question->id, []),
                        ];
                    }
                }

                return [
                    'id' => $section->id,
                    'exam_id' => $section->exam_id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'time_if_free' => $section->time_if_free,
                    'level' => $examinfo->level,
                    'questions' => $formattedQuestions,
                ];
            });

            // Calculate score statistics
            $correctAnswers = 0;
            $totalQuestions = 0;
            $scorePercentage = 0.00;
            $studentMaxScorePercentage = 0.00;

            // Get the highest score for this student in this exam
            $allScores = StudentScoreModel::where('exam_id', $data['exam_id'])
                ->where('student_id', $data['student_id'])
                ->get();

            $maxScore = null;
            $maxNumericScore = 0;

            foreach ($allScores as $scoreRecord) {
                if ($scoreRecord->score) {
                    $scoreValue = $scoreRecord->score;
                    $numericValue = null;

                    // Check if it's in format "2/9" - extract the first number
                    if (strpos($scoreValue, '/') !== false) {
                        $parts = explode('/', $scoreValue);
                        $numericValue = is_numeric($parts[0]) ? (float)$parts[0] : null;
                    } elseif (is_numeric($scoreValue)) {
                        $numericValue = (float)$scoreValue;
                    }

                    if ($numericValue !== null && $numericValue > $maxNumericScore) {
                        $maxNumericScore = $numericValue;
                        $maxScore = $numericValue;
                    }
                }
            }

            if ($check_solved_exam) {
                // Count total questions in the exam
                $totalQuestions = (int)AdminQuestionsModel::whereIn('exam_section_id', $sections->pluck('id'))->count();

                // Count correct answers from student
                $correctAnswers = (int)StudentAnswerModel::where('exam_id', $data['exam_id'])
                    ->where('student_id', $data['student_id'])
                    ->where('is_correct', 1)
                    ->count();

                // Calculate percentage based on last score
                if ($totalQuestions > 0 && $lastScore !== null && is_numeric($lastScore)) {
                    $scorePercentage = round(((float)$lastScore / $totalQuestions) * 100, 2);
                }

                // Calculate percentage based on highest score
                if ($totalQuestions > 0 && $maxScore !== null && is_numeric($maxScore)) {
                    $studentMaxScorePercentage = round(((float)$maxScore / $totalQuestions) * 100, 2);
                }
            }

            return res_data([
                'sections' => $result,
                'exam_info' => $examinfo,
                'round_id' => $determinedRoundId ?? $examinfo->round_id ?? null,
                'previous_exam_id' => $previousExamId,
                'next_exam_id' => $nextExamId,
                'is_solved' => $check_solved_exam,
                'last_score' => $lastScore,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'score_percentage' => $scorePercentage,
                'max_score_percentage' => 100.00,
                'student_max_score_percentage' => $studentMaxScorePercentage,
            ], 'success', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessage = $e->validator->errors()->first();
            return res_data($errorMessage, 'خطأ في التحقق من البيانات', 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'خطأ غير معروف';
            return res_data($errorMessage, 'حدث خطأ أثناء جلب الأقسام', 500);
        }
    }
}
