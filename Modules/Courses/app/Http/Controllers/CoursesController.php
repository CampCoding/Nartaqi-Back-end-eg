<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Courses\Models\CategoryPartsModel;
use Modules\Courses\Models\UserRounds;
use Modules\Favourite\Models\Favourite;
use Modules\Courses\Models\SearchLogModel;

use Illuminate\Http\Request;
use Modules\Courses\Models\CourseCategories;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\StudentsRateModel;
use Modules\Courses\Transformers\CourseCategoriesResource;

use Modules\Blogs\Models\Blog as BlogModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\TeacherModel;
use Modules\Store\Models\Store;
use Modules\Authentication\Models\Student;

require_once base_path('Modules/Authentication/smsfile.php');

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:pdf,doc,docx,ppt,pptx,xls,xlsx,png,jpg,jpeg',
            'phone' => 'required',
            'name' => 'required',
        ], [
            'file.required' => 'الملف مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'name.required' => 'اسم الطالب مطلوب',
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $phone = $request->phone;
                $studentName = $request->name;
                $extension = strtolower($file->getClientOriginalExtension());

                // Construct clean filename containing "جدول_مذاكرة" + student name
                $cleanStudentName = preg_replace('/\s+/', '_', trim($studentName));
                $cleanStudentName = preg_replace('/[^A-Za-z0-9\p{Arabic}\-_]/u', '', $cleanStudentName);
                
                $fileName = 'جدول_مذاكرة_' . ($cleanStudentName ?: 'طالب') . '_' . time() . '.' . $extension;

                // Move the file manually to bypass finfo mime detection
                $targetDir = 'uploads/files';
                $file->move(storage_path('app/public/' . $targetDir), $fileName);
                $path = $targetDir . '/' . $fileName;
                
                // Generate the public URL with domain fallback
                $url = str_replace('camp-coding.site', 'nartaqi.net', asset('storage/' . $path));

                $whatsappStatus = 'Not sent (only PDF supported for automated send)';
                if ($extension === 'pdf' && $phone) {
                    $caption = "مرحباً " . ($studentName ?? 'طالبنا العزيز') . " 👋,\n\nإليك جدول المذاكرة الخاص بك 📚. نتمنى لك كل التوفيق والنجاح في مسيرتك التعليمية! 💪🌟";
                    $whatsappResponse = sendWawpPdf($phone, $url, $fileName, $caption);

                    if (isset($whatsappResponse['status']) && $whatsappResponse['status'] === 'success') {
                        $whatsappStatus = 'Sent successfully';
                    } else {
                        $whatsappStatus = 'Failed to send: ' . ($whatsappResponse['error'] ?? 'Unknown error');
                    }
                }

                return res_data([
                    'file_url' => $url,
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'student_info' => [
                        'name' => $studentName,
                        'phone' => $phone
                    ],
                    'whatsapp_status' => $whatsappStatus
                ], 'تم رفع الملف بنجاح وإرساله للطالب', 200);
            }

            return res_data(null, 'لم يتم العثور على ملف مرفق', 400);
        } catch (\Exception $e) {
            return res_data($e->getMessage(), 'حدث خطأ أثناء رفع الملف', 500);
        }
    }



    public function get_CourseCategory(Request $request)
    {
        $perPage = (int) $request->get('per_page', 5);
        // Return all categories with their rounds (no show_date filtering)
        $categories = CourseCategories::with('rounds')->paginate($perPage);

        return new CourseCategoriesResource($categories);
    }

    // don't use it in api routes
    public function get_round_by_id(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:rounds,id',
        ]);
        $round = Rounds::find($data['id']);
        return res_data($round, 'success', 200);
    }
    public function getAllCourseCategories(Request $request)
    {
        $categories = CourseCategories::where('active', 1)->get();
        return res_data($categories, 'success', 200);
    }


    public function getAllInHome(Request $request)
    {
        // Allow passing student_id explicitly (for flexible token auth) or via authenticated user
        $data = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
        ]);
        $studentId = $data['student_id'] ?? optional($request->user())->id;

        $latestRounds = Rounds::where('source', '0')
            ->where('active', '1')
            ->with(['course_categories', 'teacher'])
            ->withCount('userRounds as students_count')
            ->selectRaw('rounds.*, (
                SELECT COUNT(*)
                FROM lessons
                INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
                WHERE round_contents.round_id = rounds.id
            ) as lessons_count')
            ->latest()
            ->limit(10)
            ->get();

        $latestRounds = $latestRounds->map(function ($round) {
            $roundTotalRates = StudentsRateModel::where('round_id', '=', $round->id)->sum('rate');
            $round->roundTotalRates = $round->students_count > 0
                ? round($roundTotalRates / $round->students_count, 2)
                : 0;
            return $round;
        });

        // Attach ownership (own) and favourite (fav) flags using shared helper
        $latestRounds = $this->attachOwnershipAndFavourite($latestRounds, $studentId);

        // Course categories for home (no show_date filtering)
        $categories = CourseCategories::with(['category_parts' => function ($q) {
            $q->withCount(['rounds' => function ($query) {
                $query->where('source', '0')->where('active', '1');
            }])
                ->orderBy('sort_number', 'asc');
        }])
            ->where('active', '1')
            ->orderBy('sort_number', 'asc')
            ->get();

        $rates = StudentsRateModel::with('student:id,name,image')->limit(10)->get();
        $latestBlogs = BlogModel::where('hidden', 0)->withCount('comments')->limit(10)->get();



        $data = [
            'latestRounds' => $latestRounds,
            'categories_with_rounds' => $categories,
            'student_rates' => $rates,
            'latestBlogs' => $latestBlogs
        ];

        return res_data($data, 'success', 200);
    }


    public function get_limit_CourseCategoryParts(Request $request)
    {
        $parts = CategoryPartsModel::query()
            ->select('category_parts.*')
            ->join('course_categories', 'category_parts.course_category_id', '=', 'course_categories.id')
            ->where('course_categories.active', '1')
            ->withCount(['rounds' => function ($query) {
                $query->where('source', '0')->where('free', '0');
            }])
            ->orderBy('course_categories.sort_number', 'asc')
            ->orderBy('category_parts.sort_number', 'asc')
            ->get();

        return res_data($parts, 'success', 200);
    }


    public function makeGeneralSearch(Request $request)
    {
        $data = $request->validate([
            'search' => 'required|string'
        ]);
        $search = $data['search'];

        // Log the search query anonymously
        SearchLogModel::logSearch($search);

        $rounds = Rounds::where('name', 'like', '%' . $search . '%')
            ->where('source', '0')
            ->with(['course_categories', 'teacher'])
            ->withCount('userRounds as students_count')
            ->selectRaw('rounds.*, (
                SELECT COUNT(*)
                FROM lessons
                INNER JOIN round_contents ON lessons.round_content_id = round_contents.id
                WHERE round_contents.round_id = rounds.id
            ) as lessons_count')
            ->latest()
            ->limit(10)
            ->get();
        $teachers = TeacherModel::where('name', 'like', '%' . $search . '%')->get();
        $blogs = BlogModel::where('title', 'like', '%' . $search . '%')->get();
        $stores = Store::where('title', 'like', '%' . $search . '%')->get();
        return res_data([
            'rounds' => $rounds,
            'teachers' => $teachers,
            'blogs' => $blogs,
            'stores' => $stores
        ], 'success', 200);
    }

    /**
     * Get the most commonly searched terms
     */
    public function getMostCommonSearches(Request $request)
    {
        $data = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $data['limit'] ?? 6;
        $mostCommonSearches = SearchLogModel::getMostCommon($limit);

        return res_data($mostCommonSearches, 'success', 200);
    }



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

    private function attachOwnershipAndFavourite(Collection $rounds, ?int $studentId): Collection
    {
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
}
