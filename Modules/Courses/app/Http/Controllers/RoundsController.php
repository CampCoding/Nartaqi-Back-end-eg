<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CategoryPartsModel;
use Modules\Courses\Models\CategryPartFreeModel;
use Modules\Courses\Models\ExamModel;
use Modules\Courses\Models\ExamPdfsModel;
use Modules\Courses\Models\ExamVideoModel;
use Modules\Courses\Models\RoundFeaturesModel;
use Modules\Courses\Models\RoundResouceModel;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\StudentScoreModel;
use Modules\Courses\Models\StudentsRateModel;
use Modules\Courses\Transformers\RoundsResource;
use Modules\Courses\Models\RoundContetModel;
use Modules\Courses\Models\LessonsModel;
use Modules\Courses\Models\VideosModel;
use Modules\Courses\Models\RoundsLiveModel;
use Illuminate\Support\Collection;
use Modules\Courses\Models\RoundTerm;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\TeachersModel;
use Modules\Favourite\Models\Favourite;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\AdminExamModel;
use Modules\Courses\Models\CourseCategories;
use Modules\Courses\Models\FreeVideosModel;
use Modules\Courses\Models\StudentView;
use Modules\Courses\Models\RoundResourceLinks;
use Modules\Courses\Models\StudentAchievementResultsModel;

class RoundsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getAllTeacher(Request $request)
    {
        $teachers = TeachersModel::all();
        $teachers->each(function ($teacher) {
            $teacher->image_url = url('storage/' . $teacher->image);
        });
        return res_data($teachers, 'success', 200);
    }

    public function makeRoundRate(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
            'rate' => 'required|numeric',
            'comment' => 'nullable',
            'recommend_to_friends' => 'nullable|string',
            'moderator_interaction' => 'nullable|string',
            'response_speed' => 'nullable|string',
            'platform_ease_of_use' => 'nullable|string',
            'notifications_rating' => 'nullable|string',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $roundRate = StudentsRateModel::where('student_id', $studentId)->where('hidden', '0')
            ->where('round_id', $data['round_id'])
            ->first();

        if ($roundRate) {
            $roundRate->update($data);
        } else {
            StudentsRateModel::create($data);
        }

        return res_data('success', 'success', 200);
    }
    public function makestudentView(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
            'video_id' => 'required|exists:videos,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        // Check if student has already viewed this video in this round
        $existingView = StudentView::where('student_id', $studentId)
            ->where('round_id', $data['round_id'])
            ->where('video_id', $data['video_id'])
            ->first();

        if ($existingView) {
            $deleteView = StudentView::where('id', $existingView->id)->delete();
            if ($deleteView) {
                return res_data('success', 'success', 200);
            } else {
                return res_data('failed', 'failed', 400);
            }
        } else {
            $studentView = StudentView::create($data);
            return res_data('success', 'success', 200);
        }
    }
    public function getRounds(Request $request)
    {
        $perPage = (int) $request->get('per_page', 5);
        $data = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $rounds = Rounds::with([
            'course_categories',
            'category_parts:id,name'
        ])->where('source', '0')->paginate($perPage);

        $collection = $this->attachTeachersToRounds($rounds->getCollection());
        $collection = $this->attachOwnershipAndFavourite($collection, $studentId);
        $rounds->setCollection($collection);

        return new RoundsResource($rounds);
    }

    public function getFreeRounds(Request $request)
    {
        $perPage = (int) $request->get('per_page', 5);
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $rounds = Rounds::where('free', '1')->where('source', '0')->with([
            'course_categories',
            'teacher',
            'category_parts:id,name'
        ])->paginate($perPage);

        $collection = $this->attachTeachersToRounds($rounds->getCollection());
        $collection = $this->attachOwnershipAndFavourite($collection, $studentId);
        $rounds->setCollection($collection);

        return new RoundsResource($rounds);
    }

    public function getRoundResources(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);
        $resources = RoundResouceModel::where('round_id', $data['round_id'])->get();
        return res_data($resources, 'success', 200);
    }

    public function getLatestRounds(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $rounds = Rounds::with(['course_categories', 'teacher'])->where('source', '0')->latest()->take(6)->get();
        $rounds = $this->attachTeachersToRounds($rounds);
        $rounds = $this->attachOwnershipAndFavourite($rounds, $studentId);
        return res_data($rounds, 'success', 200);
    }

    public function getmoreLatestRounds(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $rounds = Rounds::with(['course_categories', 'teacher', 'category_parts:id,name'])->where('source', '0')->latest()->take(value: 15)->get();
        $rounds = $this->attachTeachersToRounds($rounds);
        $rounds = $this->attachOwnershipAndFavourite($rounds, $studentId);
        return res_data($rounds, 'success', 200);
    }
    public function getRoundByFilters(Request $request)
    {
        $perPage = (int) $request->get('per_page', 5);
        $data = $request->validate([
            'filters' => 'required|array',
            'student_id' => 'sometimes|exists:students,id',
            'sort_price' => 'sometimes|string|in:low_to_high,high_to_low',
            'sort_date_latest' => 'sometimes|boolean',
            'sort_most_common' => 'sometimes|boolean',
            'sort_rating' => 'sometimes|string|in:highest,lowest',
            'gender' => 'sometimes|string|in:male,female,both', // Add gender filter
        ]);
        $filters = $data['filters'];

        $studentId = $data['student_id'] ?? optional($request->user())->id;
        $sortPrice = $data['sort_price'] ?? null;
        $sortRating = $data['sort_rating'] ?? null;
        $sortDateLatest = array_key_exists('sort_date_latest', $data) ? (bool) $data['sort_date_latest'] : true;
        $sortMostCommon = array_key_exists('sort_most_common', $data) ? (bool) $data['sort_most_common'] : true;
        $gender = $data['gender'] ?? null; // Get gender filter

        $query = Rounds::with(['course_categories', 'category_parts:id,name'])->where('source', '0')->where('active', '1')->withCount('userRounds as students_count')
            ->selectRaw('rounds.*, (
            SELECT COUNT(*)
            FROM lessons
            INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
            WHERE round_contents.round_id = rounds.id
        ) as lessons_count');

        $course_categories = CourseCategories::where('id', $filters['course_category_id'])->get();
        if ($course_categories->isEmpty()) {
            return res_data('هذه الفئه غير موجودة', 'error', 404);
        }

        // Apply gender filter
        if ($gender && $gender !== 'both') {
            $query->where('gender', $gender);
        }

        if ($this->isSequentialArray($filters)) {
            foreach ($filters as $condition) {
                if (is_array($condition) && count($condition) === 3) {
                    [$column, $operator, $value] = $condition;
                    if ($column === 'name') {
                        $query->where('name', 'like', '%' . $value . '%');
                    } else {
                        $query->where($column, $operator, $value);
                    }
                }
            }
        } else {
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
                unset($filters['name']);
            }
            if (!empty($filters)) {
                $query->where($filters);
            }
        }

        if ($sortMostCommon) {
            $query->withCount(['userRounds as enrollments_count'])
                ->orderByDesc('enrollments_count');
        } elseif ($sortRating) {
            $query->withAvg('students_rates as average_rating', 'rate')
                ->withCount('students_rates as ratings_count')
                ->orderBy('average_rating', $sortRating === 'highest' ? 'desc' : 'asc');
        } elseif ($sortPrice) {
            $query->orderBy('price', $sortPrice === 'low_to_high' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', $sortDateLatest ? 'desc' : 'asc');
        }

        $rounds = $query->paginate($perPage);

        $collection = $this->attachTeachersToRounds($rounds->getCollection());
        $collection = $this->attachOwnershipAndFavourite($collection, $studentId);

        // Calculate roundTotalRates for each round
        $collection = $collection->map(function ($round) {
            $roundTotalRates = StudentsRateModel::where('hidden', '0')->where('round_id', '=', $round->id)->sum('rate');
            $round->roundTotalRates = $round->students_count > 0
                ? round($roundTotalRates / $round->students_count, 2)
                : 0;
            return $round;
        });

        $rounds->setCollection($collection);
        $all_data = [
            'rounds' => $rounds,
            'course_categories' => $course_categories,
            'pagination' => [
                'total' => $rounds->total(),
                'per_page' => $rounds->perPage(),
                'current_page' => $rounds->currentPage(),
                'last_page' => $rounds->lastPage(),
            ],
        ];

        return res_data($all_data, 'success', 200);
    }

    public function getCategoryParts(Request $request)
    {
        $data = $request->validate([
            'course_category_id' => 'sometimes|exists:course_categories,id',
        ]);
        $categoryParts = CategoryPartsModel::where('course_category_id', $data['course_category_id'])->get();
        return res_data($categoryParts, 'success', 200);
    }

    public function getRoundByCategoryPart(Request $request)
    {
        $data = $request->validate([
            'category_part_id' => 'required|exists:category_parts,id',
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $rounds = Rounds::where('category_part_id', $data['category_part_id'])
            ->where('source', '0')
            ->with(['course_categories', 'category_parts:id,name'])
            ->get();

        if ($rounds->isEmpty()) {
            return res_data([], 'success', 200);
        }

        $rounds = $this->attachTeachersToRounds($rounds);
        $rounds = $this->attachOwnershipAndFavourite($rounds, $studentId);

        return res_data($rounds, 'success', 200);
    }

    public function getRoundsContents(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $studentId = $data['student_id'] ?? optional($request->user())->id;

        // Check if student owns the round
        $studentOwnsRound = false;
        if ($studentId) {
            $studentOwnsRound = UserRounds::where('student_id', $studentId)
                ->where('round_id', $data['round_id'])
                ->exists();
        }

        $response = $this->buildRoundContentsHierarchy($data['round_id'], $studentId, $studentOwnsRound);
        return res_data($response, 'success', 200);
    }

    // Terms & Conditions

    public function getRoundTerms(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $terms = RoundTerm::where('round_id', $data['round_id'])->get();
        return res_data($terms, 'success', 200);
    }

    public function addRoundTerm(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'title' => 'required|string',
            'points' => 'required|array',
        ]);
        $term = RoundTerm::create($data);
        return res_data($term, 'success', 200);
    }
    public function editRoundTerm(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:round_terms,id',
            'title' => 'required|string',
            'points' => 'required|array',
        ]);
        $term = RoundTerm::find($data['id']);
        $term->update($data);
        return res_data($term, 'success', 200);
    }
    public function deleteRoundTerm(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:round_terms,id',
        ]);
        $term = RoundTerm::find($data['id']);
        $term->delete();
        return res_data('success', 'success', 200);
    }
    // Course features
    public function getRoundFeatures(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);
        $features = RoundFeaturesModel::where('round_id', $data['round_id'])->get();
        return res_data($features, 'success', 200);
    }
    public function roundRate(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);
        $rate = StudentsRateModel::where('round_id', $data['round_id'])->where('hidden', '0')->with('student')->get();
        return res_data($rate, 'success', 200);
    }



    public function TopRated(Request $request)
    {

        $query = StudentsRateModel::where('hidden', '0')
            ->with(['student', 'round']);



        $rate = $query->orderBy('rate', 'desc')
            ->latest()
            ->take(6)
            ->get();

        return res_data($rate, 'success', 200);
    }



    public function GetAllRoundDataByRoundId(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'student_id' => 'sometimes|exists:students,id',
        ]);

        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $round = Rounds::with([
            'course_categories',
            'teacher',
            'category_parts',
            'round_features',
            'round_resources',
            'round_terms',
            'students_rates.student',
        ])->where('source', '0')->find($data['round_id']);

        if (!$round) {
            return res_data('هذه الدورة غير موجودة', 'error', 404);
        }

        $round = $this->attachTeachersToRound($round);
        $round = $this->attachOwnershipAndFavourite(collect([$round]), $studentId)->first();
        $studentOwnsRound = $round->own ?? false;
        $studentFavRound = $round->fav ?? false;
        $contentsResponse = $this->buildRoundContentsHierarchy($round->id, $studentId, $studentOwnsRound);

        return res_data([
            'round' => $round,
            'contents' => $contentsResponse['contents'] ?? [],
            'exams_round' => $contentsResponse['exams_round'] ?? [],
            'own' => $studentOwnsRound,
            'fav' => $studentFavRound,
        ], 'success', 200);
    }


    public function getRoundBundle(Request $request)
    {
        $data = $request->validate([
            'round_id'   => 'required|exists:rounds,id',
            'student_id' => 'sometimes|exists:students,id',
        ]);

        $roundId   = $data['round_id'];
        $studentId = $data['student_id'] ?? optional($request->user())->id; // can be null

        // Determine ownership first
        $round = Rounds::with([
            'course_categories',
            'teacher',
            'category_parts',
            'round_features',
            'round_terms',
            'students_rates.student',
        ])
            ->where('source', '0')
            ->where('id', $roundId)
            ->withCount('userRounds as students_count')
            ->selectRaw('rounds.*, (
                SELECT COUNT(*)
                FROM lessons
                INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
                WHERE round_contents.round_id = rounds.id
            ) as lessons_count')
            ->first();

        if (! $round) {
            return res_data('هذه الدورة غير موجودة', 'error', 404);
        }

        $round = $this->attachTeachersToRound($round);
        $round = $this->attachOwnershipAndFavourite(collect([$round]), $studentId)->first();

        $own = $round->own ?? false;
        $free = $round->free ?? '0  ';

        // Build contents with nested structure, passing ownership status
        $contentsResponse = $this->buildRoundContentsHierarchy($roundId, $studentId, $own);

        $features     = RoundFeaturesModel::where('round_id', $roundId)->get();
        $terms        = RoundTerm::where('round_id', $roundId)->get();
        $roundRate    = StudentsRateModel::where('round_id', $roundId)->where('hidden', '0')->with('student')->get();

        // Resources + group links
        $resources = RoundResouceModel::where('round_id', $roundId)->get();

        $links = RoundResourceLinks::where('round_id', $roundId)->first();

        $groupLinks = [];
        if ($links) {
            $groupLinks = array_filter([
                'telegram_link' => $links->telegram_link,
                'whatsapp_link' => $links->whatsapp_link,
            ], function ($value) {
                return !is_null($value);
            });
        }

        $formattedResources = $resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'round_id' => $resource->round_id,
                'title' => $resource->title,
                'description' => $resource->description,
                'url' => $resource->url,
                'show_date' => $resource->show_date,
                'created_at' => $resource->created_at,
                'updated_at' => $resource->updated_at,
            ];
        })->values()->toArray();

        $roundResourcesData = [
            'group_links' => $groupLinks,
            'resource'    => $formattedResources,
        ];


        if (!$own && $free != '1') {
            if (isset($contentsResponse['contents'])) {
                foreach ($contentsResponse['contents'] as &$content) {
                    if (isset($content['lessons'])) {
                        foreach ($content['lessons'] as &$lesson) {
                            if (isset($lesson['videos'])) {
                                foreach ($lesson['videos'] as &$video) {
                                    unset($video['youtube_link']);
                                    unset($video['vimeo_link']);
                                    unset($video['video_url']);
                                }
                            }

                            if (isset($lesson['exam_all_data'])) {
                                foreach ($lesson['exam_all_data'] as &$examData) {
                                    if (isset($examData['videos'])) {
                                        foreach ($examData['videos'] as &$video) {
                                            unset($video['youtube_link']);
                                            unset($video['vimeo_link']);
                                            unset($video['video_url']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (isset($contentsResponse['exams_round'])) {
                foreach ($contentsResponse['exams_round'] as &$examRound) {
                    if (isset($examRound['videos'])) {
                        foreach ($examRound['videos'] as &$video) {
                            unset($video['youtube_link']);
                            unset($video['vimeo_link']);
                            unset($video['video_url']);
                        }
                    }
                }
            }
        }

        // Also hide exam videos if lesson/content was_opened is false (even if student owns the round)

        if ($own && isset($contentsResponse['contents'])) {
            foreach ($contentsResponse['contents'] as &$content) {
                $contentWasOpened = $content['was_opened'] ?? false;

                if (isset($content['lessons'])) {
                    foreach ($content['lessons'] as &$lesson) {
                        // Check if lesson is not opened yet
                        $wasOpened = $lesson['was_opened'] ?? false;

                        if ((!$wasOpened || !$contentWasOpened) && isset($lesson['exam_all_data'])) {
                            foreach ($lesson['exam_all_data'] as &$examData) {
                                if (isset($examData['videos'])) {
                                    foreach ($examData['videos'] as &$video) {
                                        unset($video['youtube_link']);
                                        unset($video['vimeo_link']);
                                        unset($video['video_url']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        $roundTotalRates = StudentsRateModel::where('hidden', '0')->where('round_id', '=', $roundId)->sum('rate');
        $roundTotalRates = $round->students_count > 0
            ? round($roundTotalRates / $round->students_count, 2)
            : 0;

        $hasCoupon = \Modules\Courses\Models\Coupon::where('is_active', '1')
            ->where('target', 'rounds')
            ->where('round_id', $roundId)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($q) {
                $q->where('usage_limit', 0)->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->exists() ? 1 : 0;

        return res_data([
            'round'          => $round,
            'contents'       => $contentsResponse['contents'] ?? [],
            'exams_round'    => $contentsResponse['exams_round'] ?? [],
            'features'       => $features,
            'terms'          => $terms,
            'roundRate'      => $roundRate,
            'roundTotalRates' => $roundTotalRates,
            'roundResources' => $roundResourcesData,
            'own'            => $own,
            'fav'            => $round->fav ?? false,
            'has_coupon'     => $hasCoupon,
        ], 'success', 200);
    }

    public function getFreeVideos(Request $request)
    {

        $data = $request->validate([
            'category_part_free_id' => 'required',
        ]);

        $videos = FreeVideosModel::where('category_part_free_id', $data['category_part_free_id'])
            ->orderBy('sort_number', 'asc')
            ->get();

        // Get category part free data
        $categoryPartFree = CategryPartFreeModel::find($data['category_part_free_id']);

        if (!$categoryPartFree) {
            return res_data('Category part not found', 'error', 404);
        }

        // Format the response
        $response = [
            'free_data' => $videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'category_part_free_id' => $video->category_part_free_id,
                    'title' => $video->title,
                    'image' => $video->image,
                    'description' => $video->description,
                    'vimeo_link' => $video->vimeo_link,
                    'youtube_link' => $video->youtube_link,
                    'time' => $video->time,
                    'image_url' => $video->image_url,
                ];
            })->toArray(),
            'category_part_free' => [
                'id' => $categoryPartFree->id,
                'name' => $categoryPartFree->name,
                'image' => $categoryPartFree->image,
                'image_url' => $categoryPartFree->image_url,
            ]
        ];

        return res_data($response, 'success', 200);
    }
    public function getCourseCategoryIncludedFreeVideos(Request $request)
    {
        // Get category parts free that have free videos
        $categoriesPartsFree = CategryPartFreeModel::whereHas('free_videos')
            ->withCount('free_videos')
            ->orderBy('sort_number', 'asc')
            ->get();

        // Get category parts that have student achievement results
        $categoryPartsWithAchievements = CategoryPartsModel::whereHas('student_achievement_results')
            ->orderBy('sort_as_student_degree', 'asc')
            ->withCount('student_achievement_results')
            ->get();

        $response = [
            'parts_free_videos' => $categoriesPartsFree,
            'category_parts_with_achievements' => $categoryPartsWithAchievements,
        ];

        return res_data($response, 'success', 200);
    }

    /**
     * Get student achievement results by category part ID
     */
    public function getStudentAchievementResults(Request $request)
    {
        $data = $request->validate([
            'category_part_id' => 'required|exists:category_parts,id',
        ]);

        // Get the achievement results
        $studentAchievementResults = StudentAchievementResultsModel::where('category_part_id', $data['category_part_id'])
            ->orderBy('sort_number', 'asc')
            ->get();

        // Get the category part
        $categoryPart = CategoryPartsModel::find($data['category_part_id']);

        if (!$categoryPart) {
            return res_data('Category part not found', 'error', 404);
        }

        // Format the response
        $response = [
            'achievement_results' => $studentAchievementResults->map(function ($result) {
                return [
                    'id' => $result->id,
                    'category_part_id' => $result->category_part_id,
                    'title' => $result->title,
                    'image' => $result->image,
                    'video_link' => $result->video_link,
                    'image_url' => $result->image_url,
                ];
            })->toArray(),
            'category_part' => [
                'id' => $categoryPart->id,
                'name' => $categoryPart->name,
                'image_url' => $categoryPart->image_url,
            ]
        ];

        return res_data($response, 'success', 200);
    }


    private function buildRoundContentsHierarchy(int $roundId, ?int $studentId, bool $studentOwnsRound = false): array
    {
        // CONTENTS
        $contents = RoundContetModel::where('round_id', $roundId)
            ->orderBy('sort_number', 'asc')
            ->get(['id', 'round_id', 'title', 'description', 'type', 'show_date', 'sort_number']);

        if ($contents->isEmpty()) {
            return [];
        }

        $contentIds = $contents->pluck('id');


        // LESSONS
        $lessons = LessonsModel::whereIn('round_content_id', $contentIds)
            ->get(['id', 'round_content_id', 'title', 'description', 'type', 'show_date']);

        $lessonsByContent = $lessons->groupBy('round_content_id');
        $lessonIds = $lessons->pluck('id');


        // VIDEOS & LIVES
        $videosByLesson = $lessonIds->isNotEmpty()
            ? VideosModel::whereIn('lesson_id', $lessonIds)->where('free', '0')->get()->groupBy('lesson_id')
            : collect();

        // Get watched video IDs for this student and round
        $watchedVideoIds = collect();
        if ($studentId && $videosByLesson->isNotEmpty()) {
            $allVideoIds = $videosByLesson->flatten()->pluck('id');
            $watchedVideoIds = StudentView::where('student_id', $studentId)
                ->whereIn('video_id', $allVideoIds)
                ->pluck('video_id');
        }

        // Add watched status to each video
        $videosByLesson = $videosByLesson->map(function ($videos) use ($watchedVideoIds) {
            return $videos->map(function ($video) use ($watchedVideoIds) {
                $video->watched = $watchedVideoIds->contains($video->id);
                return $video;
            });
        });

        $livesByLesson = $lessonIds->isNotEmpty()
            ? RoundsLiveModel::whereIn('lesson_id', $lessonIds)
            ->where(function ($query) {
                // Using +02:00 to match user's local context and ensure accurate comparison
                $today = \Carbon\Carbon::now('+02:00')->toDateString();
                $now = \Carbon\Carbon::now('+02:00')->toTimeString();

                $query->where('date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->where('date', $today)
                            ->whereRaw("STR_TO_DATE(TRIM(end_time), '%h:%i %p') > ?", [$now]);
                    });
            })
            ->get()->groupBy('lesson_id')
            : collect();

        // LESSON EXAMS
        // Return videos and exam_pdfs for each lesson, even if there's no exam
        $examsByLesson = collect();

        if ($lessonIds->isNotEmpty()) {

            $assignExamsLesson = AssignExamModel::whereIn('lesson_or_round_id', $lessonIds)
                ->where('type', 'lesson')
                ->get();

            $examIds = $assignExamsLesson->pluck('exam_id')->unique();


            $exams           = AdminExamModel::whereIn('id', $examIds)->get();
            $examVideos      = ExamVideoModel::whereIn('lesson_id', $lessonIds)->where('for_type', 'lesson')->get()->groupBy('lesson_id');
            $examPdfs        = ExamPdfsModel::whereIn('lesson_id', $lessonIds)->where('for_type', 'lesson')->get()->groupBy('lesson_id');

            $examsByLesson = $lessonIds->mapWithKeys(function ($lessonId) use ($assignExamsLesson, $exams, $examVideos, $examPdfs, $studentId) {
                $assign = $assignExamsLesson->firstWhere('lesson_or_round_id', $lessonId);

                // Ensure proper type matching for exam_id
                $exam = null;
                if ($assign && $assign->exam_id) {
                    $exam = $exams->first(function ($e) use ($assign) {
                        return (int)$e->id === (int)$assign->exam_id;
                    });
                }

                $videos = ($examVideos[$lessonId] ?? collect())->values()->toArray();
                $examPdfsArray = ($examPdfs[$lessonId] ?? collect())->values()->toArray();

                if ($exam || !empty($videos) || !empty($examPdfsArray)) {
                    return [$lessonId => [
                        'exam'       => $exam,
                        'videos'     => $videos,
                        'exam_pdfs'  => $examPdfsArray,
                        'is_solved'  => $exam && $studentId
                            ? StudentScoreModel::where('exam_id', $exam->id)
                            ->where('student_id', $studentId)
                            ->exists()
                            : false,
                    ]];
                }

                return [$lessonId => null];
            })->filter();
        }

        // ROUND EXAMS (whole round)
        $examsRound = collect();

        $assignExamsRound = AssignExamModel::where('lesson_or_round_id', $roundId)
            ->where('type', 'full_round')
            ->orderBy('sort_number', 'asc')
            ->get();

        if ($assignExamsRound->isNotEmpty()) {

            $examIds     = $assignExamsRound->pluck('exam_id')->unique();
            $exams       = AdminExamModel::whereIn('id', $examIds)->get();
            // 'lesson', 'exam'
            $examVideos  = ExamVideoModel::whereIn('lesson_id', $examIds)->where('for_type', 'exam')->get()->groupBy('lesson_id');
            $examPdfs    = ExamPdfsModel::whereIn('lesson_id', $examIds)->where('for_type', 'exam')->get()->groupBy('lesson_id');

            $examsRound = $assignExamsRound->map(function ($assign) use ($studentId, $exams, $examVideos, $examPdfs) {

                $exam = $exams->firstWhere('id', $assign->exam_id);
                if (!$exam) return null;

                // Videos and PDFs are directly associated with the exam_id (stored in lesson_id column)
                $allVideos = $examVideos[$assign->exam_id] ?? collect();
                $allPdfs = $examPdfs[$assign->exam_id] ?? collect();

                return [
                    'exam'       => $exam,
                    'videos'     => $allVideos->values()->toArray(),
                    'exam_pdfs'  => $allPdfs->values()->toArray(),
                    'show_date'  => $assign->show_date,
                    'is_solved'  => $studentId
                        ? StudentScoreModel::where('exam_id', $exam->id)
                        ->where('student_id', $studentId)
                        ->exists()
                        : false,
                ];
            })->filter()->values();
        }

        // BUILD content tree
        $contentsArray = $contents->map(function ($content) use ($lessonsByContent, $videosByLesson, $livesByLesson, $examsByLesson, $studentOwnsRound) {
            // Determine if content is opened based on show_date
            $contentWasOpened = false;
            if ($studentOwnsRound) {
                if ($content->show_date === null) {
                    // If show_date is null, content is always open
                    $contentWasOpened = true;
                } else {
                    // If show_date is set, check if it has passed
                    $today = date('Y-m-d');
                    $contentWasOpened = $content->show_date <= $today;
                }
            }

            $contentLessons = ($lessonsByContent[$content->id] ?? collect())->map(function ($lesson) use ($videosByLesson, $livesByLesson, $examsByLesson, $studentOwnsRound, $contentWasOpened) {

                // Determine if lesson is opened based on show_date
                $wasOpened = false;
                if ($studentOwnsRound) {
                    if ($lesson->show_date === null) {
                        // If show_date is null, lesson is always open
                        $wasOpened = true;
                    } else {
                        // If show_date is set, check if it has passed
                        $today = date('Y-m-d');
                        $wasOpened = $lesson->show_date <= $today;
                    }
                }

                // Get videos and filter links if not opened
                $videos = ($videosByLesson[$lesson->id] ?? collect())->map(function ($video) use ($wasOpened, $contentWasOpened) {
                    $videoArray = $video->toArray();
                    if (!$wasOpened || !$contentWasOpened) {
                        unset($videoArray['youtube_link']);
                        unset($videoArray['vimeo_link']);
                        unset($videoArray['video_url']);
                    }
                    return $videoArray;
                })->values()->toArray();

                return [
                    'id'                  => $lesson->id,
                    'round_content_id'    => $lesson->round_content_id,
                    'lesson_title'        => $lesson->title,
                    'lesson_description'  => $lesson->description,
                    'lesson_type'         => $lesson->type ?? null,
                    'show_date'           => $lesson->show_date,
                    'was_opened'          => $wasOpened,
                    'videos'              => $videos,
                    'live'                => ($livesByLesson[$lesson->id] ?? collect())->values()->toArray(),
                    'exam_all_data'       => isset($examsByLesson[$lesson->id]) ? [$examsByLesson[$lesson->id]] : [],
                ];
            })->values()->toArray();

            return [
                'id'                  => $content->id,
                'round_id'            => $content->round_id,
                'content_title'       => $content->title,
                'content_description' => $content->description,
                'content_type'        => $content->type ?? null,
                'show_date'           => $content->show_date,
                'was_opened'          => $contentWasOpened,
                'lessons'             => $contentLessons,
            ];
        })->values()->toArray();


        return [
            'contents'    => $contentsArray,
            'exams_round' => $examsRound->values()->toArray(),
        ];
    }

    private function attachTeachersToRounds(Collection $rounds): Collection
    {
        return $rounds->map(fn($round) => $this->attachTeachersToRound($round));
    }

    private function attachTeachersToRound($round)
    {
        if (!$round) {
            return $round;
        }

        $teacherIds = collect(explode(',', (string) $round->teacher_id))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        $round->teachers = $teacherIds->isNotEmpty()
            ? TeachersModel::whereIn('id', $teacherIds)->get()
            : collect();

        if (method_exists($round, 'relationLoaded') && $round->relationLoaded('category_parts')) {
            $round->category_parts_name = optional($round->category_parts)->name;
            unset($round->category_parts);
        }

        return $round;
    }

    private function attachOwnershipAndFavourite(Collection $rounds, ?int $studentId): Collection
    {
        if ($rounds->isEmpty()) {
            return $rounds;
        }

        // Remove any null items to avoid errors when accessing properties
        $rounds = $rounds->filter();
        if ($rounds->isEmpty()) {
            return $rounds;
        }

        if (!$studentId) {
            return $rounds->map(function ($round) {
                $round->own = $round->own ?? false;
                $round->fav = false;
                return $round;
            });
        }

        $roundIds = $rounds->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($roundIds->isEmpty()) {
            return $rounds->map(function ($round) {
                $round->own = $round->own ?? false;
                $round->fav = false;
                return $round;
            });
        }

        $ownedRoundIds = UserRounds::where('student_id', $studentId)
            ->whereIn('round_id', $roundIds)
            ->pluck('round_id')
            ->toArray();

        $favouriteRoundIds = Favourite::where('student_id', $studentId)
            ->whereIn('round_id', $roundIds)
            ->pluck('round_id')
            ->toArray();

        return $rounds->map(function ($round) use ($ownedRoundIds, $favouriteRoundIds) {
            $round->own = in_array($round->id, $ownedRoundIds);
            $round->fav = in_array($round->id, $favouriteRoundIds);
            return $round;
        });
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    public function IntroRound(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;
        $round = Rounds::where('source', '0')->find($data['round_id']);
        if (!$round) {
            return res_data('هذه الدورة غير موجودة', 'error', 404);
        }
        $checkIntroRound = UserRounds::where('round_id', $data['round_id'])->where('student_id', $studentId)->first();
        if ($checkIntroRound) {
            return res_data('لقد انضمت  لهذه الدوره من قبل', 'error', 400);
        }

        $introRound = UserRounds::create([
            'round_id' => $data['round_id'],
            'student_id' => $studentId,
            'status' => 'active',
            'end_date' => now()->addYears(1),
            'day' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'payment_id' => null,
        ]);
        if (!$introRound) {
            return res_data('فشل إنضمامك لهذه الدورة', 'error', 400);
        }
        return res_data('تم إنضمامك لهذه الدورة بنجاح', 'success', 200);
    }

    public function getTeacherData(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id'
        ]);

        $teacher = TeachersModel::find($data['teacher_id']);

        if (!$teacher) {
            return res_data('المعلم غير موجود', 'error', 404);
        }

        // Get rounds where teacher_id matches (handles both direct match and comma-separated values)
        $rounds = Rounds::where('source', '0')->withCount('userRounds as students_count')
            ->selectRaw('rounds.*, (
            SELECT COUNT(*)
            FROM lessons
            INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
            WHERE round_contents.round_id = rounds.id
        ) as lessons_count')
            ->where(function ($query) use ($data) {
                $query->where('teacher_id', $data['teacher_id'])
                    ->orWhereRaw('FIND_IN_SET(?, teacher_id)', [$data['teacher_id']]);
            })
            ->with(['course_categories', 'category_parts:id,name'])
            ->latest()
            ->get();

        // Transform rounds to attach teachers data
        $rounds = $this->attachTeachersToRounds($rounds);

        $response = [
            'teacher' => $teacher,
            'rounds' => $rounds
        ];

        return res_data($response, 'success', 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}


    public function getRoundContentExamScore(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $roundContents = RoundContetModel::where('round_id', $data['round_id'])
            ->whereIn('type', ['basic', 'lecture'])
            ->get();
        if ($roundContents->isEmpty()) {
            return res_data([], 'No round contents found with type basic or lecture', 200);
        }

        // Separate basic and lecture content
        $basicContents = $roundContents->where('type', 'basic')->pluck('id');
        $lectureContents = $roundContents->where('type', 'lecture')->pluck('id');

        // Get scores for basic content
        $basicScores = $this->getScoresForContents($basicContents, $data['student_id']);

        // Get scores for lecture content
        $lectureScores = $this->getScoresForContents($lectureContents, $data['student_id']);

        // Get scores for exams assigned to the full round
        $roundExamScores = $this->getScoresForRoundExams($data['round_id'], $data['student_id']);

        // Calculate individual averages
        $basicAvg = $this->calculateAverageScore($basicScores);
        $lectureAvg = $this->calculateAverageScore($lectureScores);
        $examsRoundsAvg = $this->calculateAverageScore($roundExamScores);

        // Calculate total average from the three category averages
        $categoriesWithScores = [];
        if ($basicAvg > 0) $categoriesWithScores[] = $basicAvg;
        if ($lectureAvg > 0) $categoriesWithScores[] = $lectureAvg;
        if ($examsRoundsAvg > 0) $categoriesWithScores[] = $examsRoundsAvg;

        $totalAvg = count($categoriesWithScores) > 0
            ? round(array_sum($categoriesWithScores) / count($categoriesWithScores), 2)
            : 0;

        $fullData = [
            'basic' => [
                'basic_scored' => $basicScores,
                'avg_score' => $basicAvg,
            ],
            'lecture' => [
                'lecture_scored' => $lectureScores,
                'avg_score' => $lectureAvg,
            ],
            'exams_rounds' => [
                'exams_rounds_scored' => $roundExamScores,
                'avg_score' => $examsRoundsAvg,
            ],
        ];

        $result = [
            'full_data' => $fullData,
            'total_avg' => $totalAvg,
        ];

        return res_data($result, 'Student exam scores retrieved successfully', 200);
    }

    public function calculateAverageScore($scores)
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

    public function getScoresForRoundExams($roundId, $studentId)
    {
        // Get exams assigned to the full round
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

    public function getScoresForContents($contentIds, $studentId)
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
