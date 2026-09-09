<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\DeleteRoundContentRequest;
use Modules\Courses\Http\Requests\StoreRoundContentRequest;
use Modules\Courses\Http\Requests\UpdateRoundContentRequest;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Models\RoundsLiveModel;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Models\ExamPdfsModel;

class AdminRoundContentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_all_round_contents(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $roundId = (int) $data['round_id'];

        // Build the same hierarchy used in frontend round details:
        // contents -> lessons -> videos, live, exam_all_data
        $contents = RoundContetModel::where('round_id', $roundId)
            ->orderBy('sort_number', 'asc')
            ->get(['id', 'round_id', 'title', 'description', 'type', 'show_date', 'sort_number']);

        if ($contents->isEmpty()) {
            return res_data(['contents' => []], 'success', 200);
        }

        $contentIds = $contents->pluck('id');

        $lessons = LessonsModel::whereIn('round_content_id', $contentIds)
            ->get(['id', 'round_content_id', 'title', 'description', 'type', 'show_date']);
        $lessonsByContent = $lessons->groupBy('round_content_id');

        $lessonIds = $lessons->pluck('id');

        $videosByLesson = $lessonIds->isNotEmpty()
            ? VideosModel::whereIn('lesson_id', $lessonIds)->get()->groupBy('lesson_id')
            : collect();

        $livesByLesson  = $lessonIds->isNotEmpty()
            ? RoundsLiveModel::whereIn('lesson_id', $lessonIds)->get()->groupBy('lesson_id')
            : collect();


        $examsByLesson = collect();
        if ($lessonIds->isNotEmpty()) {
            $assignExamsLesson = AssignExamModel::whereIn('lesson_or_round_id', $lessonIds)
                ->where('type', 'lesson')
                ->get();

            $examIds = $assignExamsLesson->pluck('exam_id')->unique();
            $exams = AdminExamModel::whereIn('id', $examIds)->where('free', '0')->get();
            $examVideosLesson = ExamVideoModel::whereIn('lesson_id', $lessonIds)->where('for_type', 'lesson')->get()->groupBy('lesson_id');
            $examPdfsLesson = ExamPdfsModel::whereIn('lesson_id', $lessonIds)->where('for_type', 'lesson')->get()->groupBy('lesson_id');

            $examsByLesson = $lessonIds->mapWithKeys(function ($lessonId) use ($assignExamsLesson, $exams, $examVideosLesson, $examPdfsLesson) {
                $assigns = $assignExamsLesson->where('lesson_or_round_id', $lessonId);

                $videos = ($examVideosLesson[$lessonId] ?? collect())->values()->toArray();
                $examPdfs = ($examPdfsLesson[$lessonId] ?? collect())->values()->toArray();

                $lessonExam = null;
                if ($assigns->isNotEmpty()) {
                    $firstAssign = $assigns->first();
                    $lessonExam = $exams->firstWhere('id', $firstAssign->exam_id);
                }

                return [$lessonId => [
                    'exam' => $lessonExam,
                    'videos' => $videos,
                    'exam_pdfs' => $examPdfs,
                ]];
            });
        }

        $examsRound = collect();
        if ($contentIds->isNotEmpty()) {
            $assignExamsRound = AssignExamModel::where('lesson_or_round_id', $roundId)
                ->where('type', 'full_round')
                ->get();

            if ($assignExamsRound->isNotEmpty()) {
                $examIdsRound = $assignExamsRound->pluck('exam_id')->unique();
                $examsRoundModels = AdminExamModel::whereIn('id', $examIdsRound)->where('free', '0')->get();

                $roundLessons = LessonsModel::whereHas('round_content', function ($query) use ($roundId) {
                    $query->where('round_id', $roundId);
                })->pluck('id');

                $examVideosRound = ExamVideoModel::whereIn('lesson_id', $roundLessons)->get()->groupBy('lesson_id');
                $examPdfsRound = ExamPdfsModel::whereIn('lesson_id', $roundLessons)->get()->groupBy('lesson_id');

                $examsRound = $assignExamsRound->map(function ($assign) use ($examsRoundModels, $examVideosRound, $examPdfsRound, $roundLessons) {
                    $exam = $examsRoundModels->firstWhere('id', $assign->exam_id);
                    if (! $exam) {
                        return null;
                    }

                    $allVideos = collect();
                    $allPdfs = collect();
                    foreach ($roundLessons as $lessonId) {
                        $allVideos = $allVideos->merge($examVideosRound[$lessonId] ?? []);
                        $allPdfs = $allPdfs->merge($examPdfsRound[$lessonId] ?? []);
                    }

                    return [
                        'exam' => $exam,
                        'videos' => $allVideos->values()->toArray(),
                        'exam_pdfs' => $allPdfs->values()->toArray(),
                    ];
                })->filter()->values();
            }
        }

        $contentsArray = $contents->map(function ($content) use ($lessonsByContent, $videosByLesson, $livesByLesson, $examsByLesson) {
            $contentLessons = ($lessonsByContent[$content->id] ?? collect())->map(function ($lesson) use ($videosByLesson, $livesByLesson, $examsByLesson) {
                $lid = $lesson->id;
                $examData = [];
                if (isset($examsByLesson[$lid])) {
                    $lessonExamData = $examsByLesson[$lid];
                    $exam = $lessonExamData['exam'] ?? null;
                    $videos = $lessonExamData['videos'] ?? [];
                    $examPdfs = $lessonExamData['exam_pdfs'] ?? [];

                    // Create a single entry with the exam (or empty if null)
                    $examData[] = [
                        'exams' => $exam,
                        'videos' => $videos,
                        'exam_pdfs' => $examPdfs,
                    ];
                }

                return [
                    'id' => $lesson->id,
                    'round_content_id' => $lesson->round_content_id,
                    'lesson_title' => $lesson->title,
                    'lesson_description' => $lesson->description,
                    'lesson_type' => $lesson->type ?? null,
                    'lesson_show_date' => $lesson->show_date ?? null,
                    'videos' => ($videosByLesson[$lid] ?? collect())->values()->toArray(),
                    'live' => ($livesByLesson[$lid] ?? collect())->values()->toArray(),
                    'exam_all_data' => $examData,
                ];
            })->values()->toArray();

            return [
                'id' => $content->id,
                'round_id' => $content->round_id,
                'content_title' => $content->title,
                'content_show_date' => $content->show_date ?? null,
                'content_description' => $content->description,
                'content_type' => $content->type ?? null,
                'sort_number' => $content->sort_number ?? null,
                'lessons' => $contentLessons,
            ];
        })->values()->toArray();

        return res_data([
            'contents' => $contentsArray,
            'exams_round' => $examsRound->values()->toArray(),
        ], 'success', 200);
    }


    public function store_round_content(StoreRoundContentRequest $request)
    {
        $data = $request->validated();
        $maxSort = RoundContetModel::where('round_id', $data['round_id'])->max('sort_number');
        $data['sort_number'] = $maxSort ? $maxSort + 1 : 1;
        $content = RoundContetModel::create($data);
        return res_data($content, 'success', 200);
    }

    public function edit_round_content(UpdateRoundContentRequest $request)
    {
        $data = $request->validated();
        $content = RoundContetModel::find($data['id']);
        $content->update($data);
        return res_data($content, 'success', 200);
    }
    public function delete_round_content(DeleteRoundContentRequest $request)
    {
        $data = $request->validated();

        $content = RoundContetModel::find($data['id']);
        if (! $content) {
            return res_data('المحتوى غير موجود', 'error', 404);
        }

        // Check if this content has any lessons attached
        $hasLessons = LessonsModel::where('round_content_id', $content->id)->exists();
        if ($hasLessons) {
            return res_data('هذا المحتوى يحتوي على دروس، برجاء مسح الدروس بالداخل أولاً', 'error', 400);
        }

        $content->delete();
        return res_data('تم حذف المحتوى بنجاح', 'success', 200);
    }

    public function makeSortCourseCategory(Request $request)
    {
        $data = $request->validate([
            'contents' => 'required|array',
            'contents.*.id' => 'required|exists:round_contents,id',
            'contents.*.sort_number' => 'required|numeric',
        ]);

        foreach ($data['contents'] as $contentData) {
            RoundContetModel::where('id', $contentData['id'])->update(['sort_number' => $contentData['sort_number']]);
        }

        return res_data('تم ترتيب المحتويات بنجاح', 'success', 200);
    }
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
