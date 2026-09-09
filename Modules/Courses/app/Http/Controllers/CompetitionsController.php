<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CompetitionsModel;
use Modules\Courses\Http\Requests\StoreCompetitionRequest;
use Modules\Courses\Http\Requests\UpdateCompetitionRequest;
use Modules\Courses\Http\Requests\DeleteCompetitionRequest;
use Modules\Courses\Http\Requests\ShowCompetitionRequest;
use Modules\Courses\Http\Requests\ToggleCompetitionStatusRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\QuestionsBankModel;
use Modules\Courses\Models\CompetitionQuestionsModel;
use Modules\Courses\Models\CompetitionQuestionsOptionsModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Http\Requests\AddCompetitionQuestionRequest;
use Modules\Courses\Models\StudentCompetitionAnswersModel;

class CompetitionsController extends Controller
{
    /**
     * Display a listing of competitions.
     */
    public function getAllCompetitions(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $active = $request->input('active');

            $query = CompetitionsModel::query();

            // Filter by active status if provided
            if ($active !== null) {
                $query->where('active', $active);
            }

            // Order by start_date descending (newest first)
            $query->orderBy('start_date', 'desc');

            $competitions = $query->paginate($perPage);

            // Add remaining_slots for each competition
            $competitions->getCollection()->transform(function ($competition) {
                // Count existing questions
                $existingQuestionsCount = CompetitionQuestionsModel::where('competition_id', $competition->id)->count();

                // Count enrolled students
                $studentsCount = \Modules\Courses\Models\StudentCompetitionModel::where('competition_id', $competition->id)->count();

                // Calculate max questions based on type and question_type
                $maxQuestions = null;
                if ($competition->type === 'weekly') {
                    $maxQuestions = ($competition->question_type === 'single') ? 7 : 14;
                } elseif ($competition->type === 'monthly') {
                    $maxQuestions = ($competition->question_type === 'single') ? 30 : 60;
                }

                // Calculate remaining slots
                if ($maxQuestions !== null) {
                    $remainingSlots = max(0, $maxQuestions - $existingQuestionsCount);
                    $competition->remaining_slots = $remainingSlots;
                    $competition->max_questions = $maxQuestions;
                    $competition->current_questions_count = $existingQuestionsCount;
                } else {
                    // Daily competitions have no limit
                    $competition->remaining_slots = 'غير محدود';
                    $competition->max_questions = null;
                    $competition->current_questions_count = $existingQuestionsCount;
                }

                // Add students count
                $competition->students_count = $studentsCount;

                return $competition;
            });

            return response()->json([
                'status' => 'success',
                'data' => $competitions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب المسابقات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created competition in storage.
     */
    public function storeCompetition(StoreCompetitionRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('competitions', 'public');
                $data['image'] = $imagePath;
            }

            $competition = CompetitionsModel::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء المسابقة بنجاح',
                'data' => $competition
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إنشاء المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified competition.
     */
    public function showCompetition(ShowCompetitionRequest $request)
    {
        try {
            $id = $request->validated()['id'];
            $competition = CompetitionsModel::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $competition
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified competition in storage.
     */
    public function updateCompetition(UpdateCompetitionRequest $request)
    {
        try {
            $data = $request->validated();
            $id = $data['id'];
            $competition = CompetitionsModel::findOrFail($id);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($competition->image && Storage::disk('public')->exists($competition->image)) {
                    Storage::disk('public')->delete($competition->image);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('competitions', $imageName, 'public');
                $data['image'] = $imagePath;
            }

            // Remove id from data before update
            unset($data['id']);

            $competition->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث المسابقة بنجاح',
                'data' => $competition->fresh()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تحديث المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified competition from storage.
     */
    public function deleteCompetition(DeleteCompetitionRequest $request)
    {
        try {
            $id = $request->validated()['id'];
            $competition = CompetitionsModel::findOrFail($id);

            // Delete image if exists
            if ($competition->image && Storage::disk('public')->exists($competition->image)) {
                Storage::disk('public')->delete($competition->image);
            }

            $competition->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف المسابقة بنجاح'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حذف المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of a competition.
     */
    public function toggleCompetitionStatus(ToggleCompetitionStatusRequest $request)
    {
        try {
            $id = $request->validated()['id'];
            $competition = CompetitionsModel::findOrFail($id);
            $competition->active = $competition->active ? '0' : '1';
            $competition->save();

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث حالة المسابقة بنجاح',
                'data' => $competition
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تغيير حالة المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active competitions only.
     */
    public function getActiveCompetitions(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            $competitions = CompetitionsModel::where('active', true)
                ->orderBy('start_date', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $competitions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في جلب المسابقات النشطة',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function makeAutoGenerateQuestions(Request $request)
    {
        try {
            // Validate request - questions_count is now optional
            $request->validate([
                'competition_id' => 'required|exists:competitions,id',
                'questions_count' => 'nullable|integer|min:1'
            ]);

            $competitionId = $request->input('competition_id');

            // Get competition data
            $competition = CompetitionsModel::findOrFail($competitionId);
            $competitionType = $competition->type; // 'daily', 'weekly', 'monthly'
            $competitionQuestionType = $competition->question_type; // 'single', 'multi'
            $startDate = $competition->start_date;

            // Auto-calculate questions_count if not provided, based on question_type
            $questionsCount = $request->input('questions_count');

            if ($questionsCount === null) {
                // Auto-calculate based on type and question_type
                if ($competitionType === 'weekly') {
                    $questionsCount = ($competitionQuestionType === 'single') ? 7 : 14;
                } elseif ($competitionType === 'monthly') {
                    $questionsCount = ($competitionQuestionType === 'single') ? 30 : 60;
                } else {
                    // Daily competitions require manual input
                    return response()->json([
                        'status' => 'error',
                        'message' => 'المسابقات اليومية تتطلب تحديد عدد الأسئلة يدوياً',
                        'hint' => 'يرجى إضافة حقل questions_count في الطلب'
                    ], 400);
                }
            } else {
                // Validate manual questions_count based on type
                if ($competitionType === 'weekly' && !in_array($questionsCount, [7, 14])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'المسابقة الأسبوعية يجب أن تحتوي على 7 أو 14 سؤال فقط',
                        'hint' => "المسابقة ({$competitionQuestionType}) يجب أن تحتوي على " . (($competitionQuestionType === 'single') ? '7' : '14') . ' سؤال'
                    ], 400);
                }

                if ($competitionType === 'monthly' && !in_array($questionsCount, [30, 60])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'المسابقة الشهرية يجب أن تحتوي على 30 أو 60 سؤال فقط',
                        'hint' => "المسابقة ({$competitionQuestionType}) يجب أن تحتوي على " . (($competitionQuestionType === 'single') ? '30' : '60') . ' سؤال'
                    ], 400);
                }
            }

            // Get IDs of question texts already used in this competition
            $usedQuestionTexts = CompetitionQuestionsModel::where('competition_id', $competitionId)
                ->pluck('question_text')
                ->toArray();

            // Get random questions from question bank, excluding already used question texts
            $bankQuestions = QuestionsBankModel::with('options')
                ->whereNotIn('question_text', $usedQuestionTexts)
                ->where('type', 'mcq')
                ->inRandomOrder()
                ->limit($questionsCount)
                ->get();

            if ($bankQuestions->count() < $questionsCount) {
                $availableCount = QuestionsBankModel::whereNotIn('question_text', $usedQuestionTexts)->count();

                return response()->json([
                    'status' => 'error',
                    'message' => 'عدد الأسئلة المتاحة في بنك الأسئلة غير كافٍ',
                    'details' => [
                        'required' => $questionsCount,
                        'available' => $availableCount,
                        'already_used_in_competition' => count($usedQuestionTexts)
                    ]
                ], 400);
            }

            // Determine how many questions per day
            $questionsPerDay = 1;
            if ($competitionType === 'weekly' && $questionsCount === 14) {
                $questionsPerDay = 2;
            } elseif ($competitionType === 'monthly' && $questionsCount === 60) {
                $questionsPerDay = 2;
            }

            // Copy questions to competition
            $currentDate = Carbon::parse($startDate);
            $copiedQuestions = [];
            $dailyQuestionCount = 0; // Track how many questions assigned to current date

            foreach ($bankQuestions as $bankQuestion) {
                // For daily competitions, always use start_date
                // For weekly/monthly, use the sequenced currentDate
                $questionShowDate = ($competitionType === 'daily')
                    ? Carbon::parse($startDate)->format('Y-m-d')
                    : $currentDate->format('Y-m-d');

                // Create competition question
                $competitionQuestion = CompetitionQuestionsModel::create([
                    'competition_id' => $competitionId,
                    'show_date' => $questionShowDate,
                    'question_text' => $bankQuestion->question_text,
                    'question_type' => $bankQuestion->question_type
                ]);

                // Copy question options
                foreach ($bankQuestion->options as $option) {
                    CompetitionQuestionsOptionsModel::create([
                        'question_id' => $competitionQuestion->id,
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct ? '1' : '0'
                    ]);
                }

                $copiedQuestions[] = $competitionQuestion;
                $dailyQuestionCount++;

                // Move to next date ONLY for weekly/monthly (not for daily)
                if ($competitionType !== 'daily' && $dailyQuestionCount >= $questionsPerDay) {
                    $currentDate->addDay();
                    $dailyQuestionCount = 0; // Reset counter for new day
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء الأسئلة بنجاح',
                'data' => [
                    'competition_id' => $competitionId,
                    'competition_type' => $competitionType,
                    'competition_question_type' => $competitionQuestionType,
                    'questions_count' => count($copiedQuestions),
                    'questions_per_day' => $questionsPerDay,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $currentDate->subDay()->format('Y-m-d'),
                    'auto_calculated' => $request->input('questions_count') === null
                ]
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إنشاء الأسئلة',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update a competition question text and options.
     */
    public function updateComputationQuestions(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id' => 'required|exists:competition_question,id',
                'question_text' => 'nullable|string',
                'options' => 'nullable|array',
                'options.*.option_text' => 'required_with:options|string',
                'options.*.is_correct' => 'required_with:options|boolean'
            ]);

            $id = $request->input('id');
            $question = CompetitionQuestionsModel::findOrFail($id);

            // Update question text if provided
            if ($request->has('question_text')) {
                $question->update([
                    'question_text' => $request->input('question_text')
                ]);
            }

            // Update options if provided
            if ($request->has('options')) {
                // Delete old options
                CompetitionQuestionsOptionsModel::where('question_id', $id)->delete();

                // Create new options
                foreach ($request->input('options') as $optionData) {
                    CompetitionQuestionsOptionsModel::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct']
                    ]);
                }
            }

            // Load the question with options
            $question->load('options');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث السؤال بنجاح',
                'data' => $question->fresh(['options'])
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'السؤال غير موجود'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تحديث السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteComputationQuestions(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id' => 'required|exists:competition_question,id'
            ]);

            $id = $request->input('id');
            $question = CompetitionQuestionsModel::findOrFail($id);

            // Delete all options associated with this question first
            CompetitionQuestionsOptionsModel::where('question_id', $id)->delete();

            // Then delete the question
            $question->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف السؤال بنجاح'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'السؤال غير موجود'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حذف السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getComputationQuestions(Request $request)
    {
        try {
            $request->validate([
                'competition_id' => 'required|exists:competitions,id',
                'per_page' => 'nullable|integer'
            ]);

            $competitionId = $request->input('competition_id');
            $perPage = $request->input('per_page', 10);

            // Paginate directly on the query builder, not on the collection
            $questions = CompetitionQuestionsModel::where('competition_id', $competitionId)
                ->with('options')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'تم الحصول على الأسئلة بنجاح',
                'data' => $questions
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
                'message' => 'فشل في الحصول على الأسئلة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function addSingleQuestion(AddCompetitionQuestionRequest $request)
    {
        try {
            $competitionId = $request->input('competition_id');
            $questionText = $request->input('question_text');
            $questionType = $request->input('question_type');
            $options = $request->input('options');
            // Validate that at least one option is marked as correct
            $hasCorrectAnswer = collect($options)->contains('is_correct', "1");
            if (!$hasCorrectAnswer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'يجب تحديد إجابة صحيحة واحدة على الأقل'
                ], 422);
            }

            // Get competition details
            $competition = CompetitionsModel::findOrFail($competitionId);
            $competitionType = $competition->type; // daily, weekly, monthly
            $competitionQuestionType = $competition->question_type; // single, multi
            $startDate = Carbon::parse($competition->start_date);

            // Count existing questions for this competition
            $existingQuestionsCount = CompetitionQuestionsModel::where('competition_id', $competitionId)->count();

            // Determine maximum allowed questions based on competition type and question_type
            $maxQuestions = null;
            $questionsPerDay = 1; // Default

            if ($competitionType === 'weekly') {
                $maxQuestions = ($competitionQuestionType === 'single') ? 7 : 14;
                $questionsPerDay = ($competitionQuestionType === 'single') ? 1 : 2;
            } elseif ($competitionType === 'monthly') {
                $maxQuestions = ($competitionQuestionType === 'single') ? 30 : 60;
                $questionsPerDay = ($competitionQuestionType === 'single') ? 1 : 2;
            }
            // Daily competitions have no limit (maxQuestions remains null)

            // Check if adding this question would exceed the limit
            if ($maxQuestions !== null && $existingQuestionsCount >= $maxQuestions) {
                $typeLabel = $competitionType === 'weekly' ? 'الأسبوعية' : 'الشهرية';
                $questionTypeLabel = $competitionQuestionType === 'single' ? 'فردي' : 'متعدد';

                return response()->json([
                    'status' => 'error',
                    'message' => 'تجاوز الحد الأقصى للأسئلة',
                    'details' => [
                        'competition_type' => $typeLabel,
                        'question_type' => $questionTypeLabel,
                        'max_allowed' => $maxQuestions,
                        'current_count' => $existingQuestionsCount
                    ]
                ], 400);
            }

            // Auto-calculate show_date by finding the first missing date
            $showDate = null;

            if ($competitionType !== 'daily') {
                // For weekly/monthly: find first missing date in sequence
                $existingDates = CompetitionQuestionsModel::where('competition_id', $competitionId)
                    ->whereNotNull('show_date')
                    ->selectRaw('show_date, COUNT(*) as count')
                    ->groupBy('show_date')
                    ->orderBy('show_date')
                    ->get()
                    ->keyBy('show_date');

                $currentCheckDate = clone $startDate;
                $foundSlot = false;

                // Check up to max days (7 for weekly, 30 for monthly)
                $maxDays = ($competitionType === 'weekly') ? 7 : 30;

                for ($i = 0; $i < $maxDays && !$foundSlot; $i++) {
                    $dateKey = $currentCheckDate->format('Y-m-d');
                    $questionsOnThisDate = $existingDates->get($dateKey)?->count ?? 0;

                    // If this date has less than required questions per day, use it
                    if ($questionsOnThisDate < $questionsPerDay) {
                        $showDate = $dateKey;
                        $foundSlot = true;
                    }

                    $currentCheckDate->addDay();
                }

                // If no slot found (shouldn't happen if we checked limit above), use next date
                if (!$foundSlot) {
                    $showDate = $currentCheckDate->format('Y-m-d');
                }
            } else {
                // For daily competitions: always use the start_date (all questions on same date)
                $showDate = $startDate->format('Y-m-d');
            }

            // Create the question with calculated show_date
            $question = CompetitionQuestionsModel::create([
                'competition_id' => $competitionId,
                'show_date' => $showDate,
                'question_text' => $questionText,
                'question_type' => $questionType
            ]);

            // Create options for the question
            foreach ($options as $optionData) {
                CompetitionQuestionsOptionsModel::create([
                    'question_id' => $question->id,
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct']
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم إضافة السؤال بنجاح',
                'data' => $question->fresh(['options']),
                'show_date' => $showDate,
                'remaining_slots' => $maxQuestions !== null ? ($maxQuestions - $existingQuestionsCount - 1) : 'غير محدود',
                'questions_per_day' => $questionsPerDay
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'المسابقة غير موجودة'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إضافة السؤال',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getComputationFullData(Request $request)
    {
        try {
            $request->validate([
                'competition_id' => 'required|exists:competitions,id'
            ]);

            $competitionId = $request->input('competition_id');

            // Get competition info
            $competition = CompetitionsModel::find($competitionId);

            if (!$competition) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المسابقة غير موجودة'
                ], 404);
            }

            // Get competition questions with options
            $questions = CompetitionQuestionsModel::where('competition_id', $competitionId)
                ->with('options')
                ->orderBy('show_date', 'asc')
                ->get();

            // Add computed fields (same as getAllCompetitions)
            $existingQuestionsCount = $questions->count();

            // Calculate max questions based on type and question_type
            $maxQuestions = null;
            if ($competition->type === 'weekly') {
                $maxQuestions = ($competition->question_type === 'single') ? 7 : 14;
            } elseif ($competition->type === 'monthly') {
                $maxQuestions = ($competition->question_type === 'single') ? 30 : 60;
            }

            // Calculate remaining slots
            if ($maxQuestions !== null) {
                $remainingSlots = max(0, $maxQuestions - $existingQuestionsCount);
                $competition->remaining_slots = $remainingSlots;
                $competition->max_questions = $maxQuestions;
                $competition->current_questions_count = $existingQuestionsCount;
            } else {
                // Daily competitions have no limit
                $competition->remaining_slots = 'غير محدود';
                $competition->max_questions = null;
                $competition->current_questions_count = $existingQuestionsCount;
            }

            // Count enrolled students
            $studentsCount = \Modules\Courses\Models\StudentCompetitionModel::where('competition_id', $competitionId)->count();
            $competition->students_count = $studentsCount;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'competition_info' => $competition,
                    'competition_questions' => $questions
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
                'message' => 'فشل في جلب بيانات المسابقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentWithPoints(Request $request)
    {
        try {
            $request->validate([
                'competition_id' => 'required|exists:competitions,id'
            ]);

            $competitionId = $request->input('competition_id');

            // Get all students enrolled in this competition
            $enrolledStudents = \Modules\Courses\Models\StudentCompetitionModel::where('competition_id', $competitionId)
                ->with('student')
                ->get();

            // Calculate points for each student
            $studentsWithPoints = $enrolledStudents->map(function ($enrollment) use ($competitionId) {
                $studentId = $enrollment->student_id;
                $student = $enrollment->student;

                // Count correct answers (points) for this student in this competition
                $points = StudentCompetitionAnswersModel::where('competition_id', $competitionId)
                    ->where('student_id', $studentId)
                    ->where('correct_or_not', '1')
                    ->count();

                // Count total answers
                $totalAnswers = StudentCompetitionAnswersModel::where('competition_id', $competitionId)
                    ->where('student_id', $studentId)
                    ->count();

                // Count wrong answers
                $wrongAnswers = $totalAnswers - $points;

                // Calculate score percentage
                $scorePercentage = $totalAnswers > 0 ? round(($points / $totalAnswers) * 100, 2) : 0;

                return [
                    'student_id' => $studentId,
                    'student_name' => $student->name ?? null,
                    'image_url' => $student->image_url ?? null,
                    'gender' => $student->gender ?? null,
                    'enrollment_date' => $enrollment->date_created,
                    'points' => $points,
                    'total_answers' => $totalAnswers,
                    'wrong_answers' => $wrongAnswers,
                    'score_percentage' => $scorePercentage
                ];
            });

            $studentsWithPoints = $studentsWithPoints->sortByDesc('points')->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'competition_id' => $competitionId,
                    'total_students' => $studentsWithPoints->count(),
                    'students' => $studentsWithPoints
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
                'message' => 'فشل في جلب بيانات الطلاب',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
