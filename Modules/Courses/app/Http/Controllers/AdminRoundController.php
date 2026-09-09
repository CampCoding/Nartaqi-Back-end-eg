<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Http\Requests\CreateRoundRequest;
use Modules\Courses\Http\Requests\EditRoundRequest;
use Modules\Courses\Http\Requests\DeleteRoundRequest;
use Modules\Courses\Http\Requests\GetAllStudentInRoundRequest;
use Modules\Courses\Http\Requests\ToggleShowRoundBookRequest;
use Modules\Courses\Http\Requests\ActiveAndInactivRoundRequest;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Models\RoundsLiveModel;
use Modules\Courses\Models\RoundFeaturesModel;
use Modules\Courses\Models\RoundResouceModel;
use Modules\Courses\Models\RoundResourceLinks;
use Modules\Courses\Models\RoundTerm;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\TeachersModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\StudentsRateModel;
use Modules\Courses\Services\ExamCopyService;

class AdminRoundController extends Controller
{
    public function __construct(private ExamCopyService $examCopyService)
    {
    }

    public function get_all_rounds(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $courseCategoryId = $request->get('course_category_id');
        $teacherFilter = $request->get('teacher_id');




        $query = Rounds::where('source', '0')
            ->with(['course_categories', 'teacher', 'round_terms']);

        if ($courseCategoryId) {
            $query->where('course_category_id', $courseCategoryId);
        }

        if ($teacherFilter) {
            $filterIds = collect(explode(',', $teacherFilter))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            if ($filterIds->isNotEmpty()) {
                $query->where(function ($q) use ($filterIds) {
                    foreach ($filterIds as $id) {
                        $q->orWhereRaw('FIND_IN_SET(?, teacher_id)', [$id]);
                    }
                });
            }
        }

        $rounds = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $rounds->getCollection()->transform(function ($round) {
            $teacherIds = collect(explode(',', (string) $round->teacher_id))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            $round->teachers = $teacherIds->isEmpty()
                ? collect()
                : TeachersModel::whereIn('id', $teacherIds)->get(['id', 'name', 'image', 'description']);

            return $round;
        });

        return res_data($rounds, 'success', 200);
    }

    public function get_solo_round_data(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $roundId = (int) $data['round_id'];

        $round = Rounds::find($roundId);
        return res_data($round, 'success', 200);
    }




    public function getRoundFreeVideos(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required',
        ]);

        $round = $data['round_id'];
        $videos = VideosModel::where('lesson_id', $round)
            ->where('free', '1')
            ->get();

        return res_data($videos, 'success', 200);
    }

    public function getsourceRound(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $rounds = Rounds::where('source', '1')->with('round_terms')->orderBy('created_at', 'desc')->paginate($perPage);
        return res_data($rounds, 'success', 200);
    }

    public function store_round(CreateRoundRequest $request)
    {
        $data = $request->validated();

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'rounds' . '/' . $imageName;
        }
        if (request()->hasFile('round_road_map_book')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $round_road_map_book = request()->file('round_road_map_book');
            $round_road_map_bookName = time() . '_' . uniqid() . '.' . $round_road_map_book->getClientOriginalExtension();
            $round_road_map_book->move($destinationPath, $round_road_map_bookName);
            $data['round_road_map_book'] = 'rounds' . '/' . $round_road_map_bookName;
        }
        if (request()->hasFile('round_book')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $round_book = request()->file('round_book');
            $round_bookName = time() . '_' . uniqid() . '.' . $round_book->getClientOriginalExtension();
            $round_book->move($destinationPath, $round_bookName);
            $data['round_book'] = 'rounds' . '/' . $round_bookName;
        }
        // $data['active'] = (int) ($data['active'] ?? 1);

        $round = Rounds::create($data);
        if ($round) {
            return  res_data([
                'message' => 'تم إنشاء الجولة بنجاح',
                'round_id' => $round->id,
            ], 'success', 201);
        }
        return  res_data('فشل إنشاء الجولة', 'error', 400);
    }

    public function edit_round(EditRoundRequest $request)
    {
        $data = $request->validated();
        $round = Rounds::find($data['id']);
        if (! $round) {
            return  res_data('الجولة غير موجودة', 'error', 404);
        }

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'rounds' . '/' . $imageName;
        }

        if (request()->hasFile('round_road_map_book')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $round_road_map_book = request()->file('round_road_map_book');
            $round_road_map_bookName = time() . '_' . uniqid() . '.' . $round_road_map_book->getClientOriginalExtension();
            $round_road_map_book->move($destinationPath, $round_road_map_bookName);
            $data['round_road_map_book'] = 'rounds' . '/' . $round_road_map_bookName;
        }

        if (request()->hasFile('round_book')) {
            $destinationPath = public_path('storage/' . 'rounds');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $round_book = request()->file('round_book');
            $round_bookName = time() . '_' . uniqid() . '.' . $round_book->getClientOriginalExtension();
            $round_book->move($destinationPath, $round_bookName);
            $data['round_book'] = 'rounds' . '/' . $round_bookName;
        }

        $round->update($data);
        if ($round) {
            return  res_data('تم تعديل الجولة بنجاح', 'success', 200);
        }
        return  res_data('فشل تعديل الجولة', 'error', 400);
    }

    public function delete_round(DeleteRoundRequest $request)
    {
        $data = $request->validated();
        $round = Rounds::find($data['id']);
        if (! $round) {
            return  res_data('الجولة غير موجودة', 'error', 404);
        }
        $round->delete();
        return  res_data('تم حذف الجولة بنجاح', 'success', 200);
    }

    public function active_round(ActiveAndInactivRoundRequest $request)
    {
        $data = $request->validated();
        $round = Rounds::find($data['id']);
        if (! $round) {
            return  res_data('الجولة غير موجودة', 'error', 404);
        }

        if ((int) $data['active'] === 1) {
            $missingFields = [];

            // if (is_null($round->teacher_id) || $round->teacher_id === '') {
            //     $missingFields[] = 'اضف مدرس في الدوره اولاٌ';
            // }
            if (is_null($round->time_show) || $round->time_show === '') {
                $missingFields[] = 'اضف وقت العرض في الدوره اولاٌ';
            }
            if (is_null($round->start_date)) {
                $missingFields[] = 'تاريخ البدء غير معروف يجب تعديله اولا ';
            }
            if (is_null($round->end_date)) {
                $missingFields[] = 'تاريخ الانتهاء غير معروف يجب تعديله اولا';
            }

            // Check if any round_resources have null show_date
            $resourcesWithoutDate = RoundResouceModel::where('round_id', $round->id)
                ->whereNull('show_date')
                ->exists();

            if ($resourcesWithoutDate) {
                $missingFields[] = 'تاريخ العرض للموارد غير معروف يجب تعديله اولا';
            }

            // If there are missing fields, return error
            if (!empty($missingFields)) {
                return res_data(
                    'لا يمكن تفعيل الدورة. الحقول التالية مطلوبة: ' . implode(', ', $missingFields),
                    'error',
                    400
                );
            }
        }

        $round->update(['active' => (int) $data['active']]);
        return  res_data('تم تعديل حالة الجولة بنجاح', 'success', 200);
    }

    public function getAllStudentInRound(GetAllStudentInRoundRequest $request)
    {
        $data = $request->validated();

        // Get all student subscriptions for this round from student_rounds table
        // Include student data through the relationship
        $subscriptions = UserRounds::where('round_id', $data['round_id'])
            ->with('student') // Eager load complete student data
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform the data to put student info first with subscription details nested
        $formattedData = $subscriptions->map(function ($subscription) {
            $studentData = $subscription->student ? $subscription->student->toArray() : null;

            // Remove unwanted fields from student data
            if ($studentData) {
                unset($studentData['created_at'], $studentData['updated_at'], $studentData['token']);
            }

            return array_merge(
                $studentData ?? ['student_id' => $subscription->student_id],
                [
                    'subscription_info' => [
                        'id' => $subscription->id,
                        'round_id' => $subscription->round_id,
                        'payment_id' => $subscription->payment_id,
                        'end_date' => $subscription->end_date,
                        'day' => $subscription->day,
                        'time' => $subscription->time,
                        'status' => $subscription->status,
                        'created_at' => $subscription->created_at,
                        'updated_at' => $subscription->updated_at,
                    ]
                ]
            );
        });

        return res_data($formattedData, 'success', 200);
    }

    public function makeCopyRoundWithAllData(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'time_show' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'show_resources_date' => 'sometimes|date',
            'source' => 'sometimes|in:0,1',
        ]);

        $originalRound = Rounds::findOrFail($data['round_id']);
        $showResourcesDate = $data['show_resources_date'] ?? null;
        $originalRound->time_show = $data['time_show'];
        $originalRound->start_date = $data['start_date'];
        $originalRound->end_date = $data['end_date'];
        $originalRound->save();
        if ($showResourcesDate) {
            $originalRound->round_resources()->update(['show_date' => $showResourcesDate]);
        }
        $newRound = null;

        DB::transaction(function () use ($originalRound, $data, &$newRound, $showResourcesDate) {
            $examIdMap = [];
            $copyExamWithQuestions = fn (int $examId) => $this->examCopyService->copyExamWithQuestions($examId, $examIdMap);

            $newRound = $originalRound->replicate();
            $newRound->name = $data['name'];
            $newRound->source = $data['source'] ?? '0';
            $newRound->active = $originalRound->active;
            $newRound->created_at = now();
            $newRound->updated_at = now();
            $newRound->time_show = $data['time_show'];
            $newRound->start_date = $data['start_date'];
            $newRound->end_date = $data['end_date'];
            $newRound->save();

            foreach ($originalRound->round_features as $feature) {
                $newFeature = $feature->replicate();
                $newFeature->round_id = $newRound->id;
                $newFeature->save();
            }

            foreach ($originalRound->round_resources as $resource) {
                $newResource = $resource->replicate();
                $newResource->round_id = $newRound->id;
                $newResource->show_date = $showResourcesDate ?? $resource->show_date;
                $newResource->save();
            }

            foreach ($originalRound->round_terms as $term) {
                $newTerm = $term->replicate();
                $newTerm->round_id = $newRound->id;
                $newTerm->save();
            }

            // Copy round resource links
            $resourceLinks = RoundResourceLinks::where('round_id', $originalRound->id)->get();
            foreach ($resourceLinks as $resourceLink) {
                $newResourceLink = $resourceLink->replicate();
                $newResourceLink->round_id = $newRound->id;
                $newResourceLink->save();
            }

            $originalContents = RoundContetModel::where('round_id', $originalRound->id)->get();

            foreach ($originalContents as $content) {
                $newContent = $content->replicate();
                $newContent->round_id = $newRound->id;
                $newContent->save();

                $originalLessons = LessonsModel::where('round_content_id', $content->id)->get();

                foreach ($originalLessons as $lesson) {
                    $newLesson = $lesson->replicate();
                    $newLesson->round_content_id = $newContent->id;
                    $newLesson->save();

                    // Copy lesson videos only (not free videos)
                    $videos = VideosModel::where('lesson_id', $lesson->id)->get();
                    foreach ($videos as $video) {
                        $newVideo = $video->replicate();
                        $newVideo->lesson_id = $newLesson->id;
                        $newVideo->save();
                    }

                    $lives = RoundsLiveModel::where('lesson_id', $lesson->id)->get();
                    foreach ($lives as $live) {
                        $newLive = $live->replicate();
                        $newLive->lesson_id = $newLesson->id;
                        $newLive->save();
                    }

                    // Copy exam videos and PDFs linked to this lesson
                    $examVideos = ExamVideoModel::where('lesson_id', $lesson->id)->get();
                    foreach ($examVideos as $examVideo) {
                        $newExamVideo = $examVideo->replicate();
                        $newExamVideo->lesson_id = $newLesson->id;
                        $newExamVideo->save();
                    }

                    $examPdfs = ExamPdfsModel::where('lesson_id', $lesson->id)->get();
                    foreach ($examPdfs as $examPdf) {
                        $newExamPdf = $examPdf->replicate();
                        $newExamPdf->lesson_id = $newLesson->id;
                        $newExamPdf->save();
                    }


                    // Copy assigned exams for this lesson
                    // Scenario: In assign_exam_round table, when type='lesson'
                    // - lesson_or_round_id = old lesson_id (lesson.id)
                    // - exam_id = the exam to copy
                    // We need to:
                    // 1. Copy the exam with all questions (using copyExamWithQuestions)
                    // 2. Create new assignment with new lesson_id and new exam_id
                    $lessonExamAssignments = AssignExamModel::where('lesson_or_round_id', $lesson->id)
                        ->where('type', 'lesson')
                        ->get();

                    foreach ($lessonExamAssignments as $assignment) {
                        // Copy exam with all its questions, sections, paragraphs
                        $newExam = $copyExamWithQuestions((int) $assignment->exam_id);
                        if (!$newExam) {
                            continue;
                        }

                        // Create new assignment with updated IDs
                        $newAssignment = $assignment->replicate();
                        $newAssignment->lesson_or_round_id = $newLesson->id; // Map to new lesson
                        $newAssignment->exam_id = $newExam->id; // Map to new exam
                        $newAssignment->save();
                    }
                }
            }


            $freeVideos = VideosModel::where('lesson_id', $originalRound->id)
                ->where('free', '1')
                ->get();

            foreach ($freeVideos as $freeVideo) {
                $newFreeVideo = $freeVideo->replicate();
                $newFreeVideo->lesson_id = $newRound->id;
                $newFreeVideo->save();
            }



            $roundExamAssignments = AssignExamModel::where('lesson_or_round_id', $originalRound->id)
                ->where('type', 'full_round')
                ->get();

            foreach ($roundExamAssignments as $assignment) {
                // Copy exam with all its questions, sections, paragraphs
                $newExam = $copyExamWithQuestions((int) $assignment->exam_id);
                if (!$newExam) {
                    continue;
                }

                // Create new assignment with updated IDs
                $newAssignment = $assignment->replicate();
                $newAssignment->lesson_or_round_id = $newRound->id; // Map to new round
                $newAssignment->exam_id = $newExam->id; // Map to new exam
                $newAssignment->save();

                // Copy Exam Videos where lesson_id is the exam_id
                $examVideos = ExamVideoModel::where('lesson_id', $assignment->exam_id)->get();
                foreach ($examVideos as $video) {
                    $newVideo = $video->replicate();
                    $newVideo->lesson_id = $newExam->id;
                    $newVideo->save();
                }

                // Copy Exam PDFs where lesson_id is the exam_id
                $examPdfs = ExamPdfsModel::where('lesson_id', $assignment->exam_id)->get();
                foreach ($examPdfs as $pdf) {
                    $newPdf = $pdf->replicate();
                    $newPdf->lesson_id = $newExam->id;
                    $newPdf->save();
                }
            }
        });


        if (! $newRound) {
            return res_data('فشل إنشاء نسخة من الجولة', 'error', 400);
        }

        $newRound->load([
            'round_features',
            'round_resources',
            'round_terms',
            'round_contents',
        ]);

        return res_data($newRound, 'success', 201);
    }


    /**
     * Copy only specific lessons (and their videos/lives/exam videos+pdfs/assigned exams)
     * from a round, either into a brand new round or appended into an existing one.
     */
    public function copyRoundContent(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'lesson_ids' => 'required|array|min:1',
            'lesson_ids.*' => 'integer|exists:lessons,id',
            'target_round_id' => 'nullable|exists:rounds,id',
            'name' => 'required_without:target_round_id|string|max:255',
            'time_show' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'source' => 'sometimes|in:0,1',
        ]);

        $originalRound = Rounds::findOrFail($data['round_id']);

        $lessons = LessonsModel::whereIn('id', $data['lesson_ids'])
            ->with('round_content')
            ->get();

        $sourceContentIds = RoundContetModel::where('round_id', $originalRound->id)->pluck('id');
        $invalidLesson = $lessons->first(fn ($lesson) => !$sourceContentIds->contains($lesson->round_content_id));
        if ($invalidLesson) {
            return res_data('أحد الدروس المختارة لا يتبع الدورة المحددة', 'error', 422);
        }

        if ($lessons->count() !== count($data['lesson_ids'])) {
            return res_data('أحد الدروس المختارة غير موجود', 'error', 422);
        }

        $newRound = null;
        $copiedLessonsCount = 0;

        DB::transaction(function () use ($originalRound, $data, $lessons, &$newRound, &$copiedLessonsCount) {
            if (!empty($data['target_round_id'])) {
                $newRound = Rounds::findOrFail($data['target_round_id']);
            } else {
                $newRound = $originalRound->replicate();
                $newRound->name = $data['name'];
                $newRound->source = $data['source'] ?? '0';
                $newRound->active = $originalRound->active;
                $newRound->created_at = now();
                $newRound->updated_at = now();
                $newRound->time_show = $data['time_show'] ?? $originalRound->time_show;
                $newRound->start_date = $data['start_date'] ?? $originalRound->start_date;
                $newRound->end_date = $data['end_date'] ?? $originalRound->end_date;
                $newRound->save();
            }

            $examIdMap = [];
            $contentMap = [];
            $lessonsByContent = $lessons->groupBy('round_content_id');

            foreach ($lessonsByContent as $sourceContentId => $contentLessons) {
                $sourceContent = $contentLessons->first()->round_content;

                if (!isset($contentMap[$sourceContentId])) {
                    $newContent = $sourceContent->replicate();
                    $newContent->round_id = $newRound->id;
                    $newContent->save();
                    $contentMap[$sourceContentId] = $newContent->id;
                }
                $newContentId = $contentMap[$sourceContentId];

                foreach ($contentLessons as $lesson) {
                    $newLesson = $lesson->replicate();
                    $newLesson->round_content_id = $newContentId;
                    $newLesson->save();
                    $copiedLessonsCount++;

                    foreach (VideosModel::where('lesson_id', $lesson->id)->get() as $video) {
                        $newVideo = $video->replicate();
                        $newVideo->lesson_id = $newLesson->id;
                        $newVideo->save();
                    }

                    foreach (RoundsLiveModel::where('lesson_id', $lesson->id)->get() as $live) {
                        $newLive = $live->replicate();
                        $newLive->lesson_id = $newLesson->id;
                        $newLive->save();
                    }

                    foreach (ExamVideoModel::where('lesson_id', $lesson->id)->get() as $examVideo) {
                        $newExamVideo = $examVideo->replicate();
                        $newExamVideo->lesson_id = $newLesson->id;
                        $newExamVideo->save();
                    }

                    foreach (ExamPdfsModel::where('lesson_id', $lesson->id)->get() as $examPdf) {
                        $newExamPdf = $examPdf->replicate();
                        $newExamPdf->lesson_id = $newLesson->id;
                        $newExamPdf->save();
                    }

                    $lessonExamAssignments = AssignExamModel::where('lesson_or_round_id', $lesson->id)
                        ->where('type', 'lesson')
                        ->get();

                    foreach ($lessonExamAssignments as $assignment) {
                        $newExam = $this->examCopyService->copyExamWithQuestions((int) $assignment->exam_id, $examIdMap);
                        if (!$newExam) {
                            continue;
                        }

                        $newAssignment = $assignment->replicate();
                        $newAssignment->lesson_or_round_id = $newLesson->id;
                        $newAssignment->exam_id = $newExam->id;
                        $newAssignment->save();
                    }
                }
            }
        });

        if (!$newRound) {
            return res_data('فشل نسخ المحتوى المحدد', 'error', 400);
        }

        $newRound->load(['round_contents.lessons']);

        return res_data([
            'round' => $newRound,
            'copied_lessons_count' => $copiedLessonsCount,
        ], 'success', 201);
    }

    public function getStudentRateRound(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $ratings = StudentsRateModel::where('round_id', $data['round_id'])
            ->with(['student:id,name,phone,gender'])
            ->orderBy('created_at', 'desc')
            ->get();

        return res_data($ratings, 'success', 200);
    }



    public function showHiddenStudentRate(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:student_rates,id',
        ]);

        $studentRate = StudentsRateModel::find($data['id']);
        if (!$studentRate) {
            return res_data('التقييم غير موجود', 'failed', 404);
        }

        // Toggle the hidden status
        $studentRate->hidden = $studentRate->hidden == '1' ? '0' : '1';
        $studentRate->save();

        $message = $studentRate->hidden == 0 ? 'تم إظهار التقييم بنجاح' : 'تم إخفاء التقييم بنجاح';
        return res_data($message, 'success', 200);
    }

    public function toggleShowRoundBook(ToggleShowRoundBookRequest $request)
    {
        $round = Rounds::find($request->round_id);

        $round->show_round_book = $round->show_round_book == '1' ? '0' : '1';
        $round->save();

        $message = $round->show_round_book == '1' ? 'تم إظهار كتاب الدورة بنجاح' : 'تم إخفاء كتاب الدورة بنجاح';
        return res_data($message, 'success', 200);
    }
}
