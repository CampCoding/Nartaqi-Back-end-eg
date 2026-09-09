<?php

namespace Modules\Blogs\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Blogs\Models\BlogComment;
use Modules\Blogs\Http\Requests\BlogCommentRequest;

class AdminBlogCommentsController extends Controller
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

        $comments = BlogComment::where('blog_id',$blog_id)->get();
        return res_data($comments,'success',200);

    }

    public function deleteComment(Request $request){

        $validatedId = $request->validate([
            'id' => 'required|integer',
        ]);

        $comment = BlogComment::find($validatedId['id']);
        if(!$comment){
            return res_data('هذا التعليق غير موجود','failed',404);
        }
        $comment->delete();
        return res_data('تم الحذف بنجاح','sucess',200);
    }


    public function showHideComment(Request $request){
        $data = $request->validate([
            'id' => 'required|exists:blog_comments,id',
            'hidden' => 'required|in:0,1',
        ]);

        $comment = BlogComment::find($data['id']);
        if(!$comment){
            return res_data('هذا التعليق غير موجود','failed',404);
        }
        $comment->hidden = $data['hidden'];
        $comment->save();

        $message = $data['hidden'] == 0 ? 'تم إظهار التعليق بنجاح' : 'تم إخفاء التعليق بنجاح';
        return res_data($message,'success',201);
    }
}
