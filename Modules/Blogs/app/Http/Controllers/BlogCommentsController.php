<?php

namespace Modules\Blogs\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Blogs\Models\BlogComment;
use Modules\Blogs\Http\Requests\BlogCommentRequest;

class BlogCommentsController extends Controller
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

    public function getBlogComments(){

        $blog_id = request()->blog_id;
        if(!$blog_id){
            return res_data('يجب ارسال معرف المقال','failed',400);
        }

        // $blogs = BlogComment::with('student')->where('blog_id',$blog_id)->where('hidden', 0)->get();
        $blogs = BlogComment::with([
            'student:id,name,image',
            'blog:id,title,image'
        ])
        ->where('blog_id',$blog_id)
        ->where('hidden', 0)
        ->get();
        return res_data($blogs,'تم الاسترجاع بنجاح',200);

    }

    public function addComment(BlogCommentRequest $request)
    {
        $student = $request->user();

        if (!$student) {
            return res_data('المستخدم غير مصرح له. يرجى تسجيل الدخول', 'error', 401);
        }

        $data = $request->validated();
        $data['student_id'] = $student->id;

        $blog = BlogComment::create($data);

        return res_data($blog, 'تم الاضافة بنجاح', 200);
    }


}
