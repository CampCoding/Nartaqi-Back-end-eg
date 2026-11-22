<?php

namespace Modules\Blogs\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Blogs\Models\Blog;
use App\Http\Controllers\Controller;
use Modules\Blogs\Http\Requests\BlogRequest;

class BlogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('blogs::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('blogs::create');
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
        return view('blogs::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('blogs::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    /**
     * Blog list page: published blogs only, newest publish date first, paginated,
     * an excerpt instead of the full content.
     */
    public function getBlogs(Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 12), 50));

        $blogs = Blog::published()
            // select() must come before withCount(), otherwise it drops comments_count.
            ->select('id', 'slug', 'title', 'content', 'image', 'image_cover', 'image_alt',
                'meta_description', 'published_at', 'updated_at', 'views', 'created_at')
            ->withCount(['comments' => fn ($q) => $q->where('hidden', 0)])
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'))
            ->orderByDesc('id')
            ->paginate($perPage);

        $blogs->getCollection()->transform(function ($blog) {
            $blog->excerpt = $this->excerpt($blog->content);
            unset($blog->content);
            return $blog;
        });

        return res_data($blogs, 'تم الاسترجاع بنجاح', 200);
    }

    /**
     * One blog by id (old links) or slug. Does not count a view: the site calls `view` from the browser.
     */
    public function getBlog(Request $request)
    {
        $key = $request->input('slug') ?? $request->input('id');
        if (blank($key) || !is_scalar($key)) {
            return res_data('يجب ادخال معرف المقال', 'failed', 400);
        }
        $key = (string) $key;

        $query = Blog::with([
            'comments' => function ($q) {
                $q->where('hidden', 0)
                    ->with('student:id,name,image');
            }
        ])
            ->withCount(['comments' => function ($q) {
                $q->where('hidden', 0);
            }])
            ->published();

        $blog = is_numeric($key)
            ? $query->find((int) $key)
            // The site may send the slug still URL-encoded (Arabic). A real slug never contains "%".
            : $query->where('slug', str_contains($key, '%') ? rawurldecode($key) : $key)->first();

        if (!$blog) {
            return res_data('المقال غير موجود', 'failed', 404);
        }

        $blog->related_blogs = $blog->relatedBlogs();
        return res_data($blog, 'success', 200);
    }

    /**
     * Count one view. Sent by the site from the visitor's browser only, so server renders and
     * search bots no longer inflate the number. Does not touch updated_at (that is "last edited").
     */
    public function addView(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer']);

        Blog::published()->where('id', $data['id'])->toBase()->increment('views');

        return res_data('ok', 'success', 200);
    }

    /** Light list for the sitemap: every published blog's link data, no content. */
    public function sitemap()
    {
        $blogs = Blog::published()
            ->select('id', 'slug', 'image_cover', 'updated_at')
            ->orderByDesc('updated_at')
            ->get()
            ->each(fn ($blog) => $blog->setAppends(['image_cover_url']));

        return res_data($blogs, 'success', 200);
    }

    /** Plain-text start of the content for the blog cards. */
    private function excerpt(?string $content, int $length = 220): string
    {
        // A space before each tag keeps words of separate paragraphs apart.
        $text = strip_tags(str_replace('<', ' <', (string) $content));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($text, $length);
    }
}
