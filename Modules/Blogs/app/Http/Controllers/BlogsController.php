<?php

namespace Modules\Blogs\Http\Controllers;

use Illuminate\Http\Request;
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

    public function getBlogs(){

        $blogs = Blog::where('hidden', 0)->withCount('comments')->latest()->get()
        ->map(function ($blog) {
            $blog->related_blogs = $blog->relatedBlogs();
            return $blog;
        });
        return res_data($blogs,'تم الاسترجاع بنجاح',200);

    }

    public function getBlog(Request $request){

        $id = $request->id;
        if(!$id){
            return res_data('يجب ادخال معرف المقال','failed',400);
        }
        $blog = Blog::with([
            'comments' => function ($q) {
                $q->where('hidden', 0)
                  ->with('student:id,name,image');
            }
        ])
        ->withCount(['comments' => function ($q) {
            $q->where('hidden', 0);
        }])
        ->where('hidden', 0)
        ->find($id);

        if(!$blog){
            return res_data('المقال غير موجود','failed',404);
        }
        
        $blog->related_blogs = $blog->relatedBlogs();
        $blog->increment('views');
        return res_data($blog,'success',200);
    }
}
