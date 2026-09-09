<?php

namespace Modules\Blogs\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Blogs\Models\Blog;
use App\Http\Controllers\Controller;
use Modules\Blogs\Http\Requests\BlogRequest;

class AdminBlogsController extends Controller
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


    public function getBlogsAdmin()
    {

        $blogs = Blog::all();
        return res_data($blogs, 'تم الاسترجاع بنجاح', 200);
    }

    public function addBlog(BlogRequest $request)
    {

        $validated = $request->validated();

        if (isset($validated['published_at'])) {
            $validated['published_at'] = \Carbon\Carbon::parse($validated['published_at'])->format('Y-m-d H:i:s');
        }

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'blogs');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $validated['image'] = 'blogs' . '/' . $imageName;
            $validated['image_cover'] = $validated['image'];
        }
        Blog::create($validated);

        return res_data('تم رفع المقال بنجاح', 'success', 200);
    }

    public function updateBlog(BlogRequest $request)
    {

        $data = $request->validated();

        if (isset($data['published_at'])) {
            $data['published_at'] = \Carbon\Carbon::parse($data['published_at'])->format('Y-m-d H:i:s');
        }

        $validatedId = $request->validate([
            'id' => 'required|exists:blogs,id',
        ]);

        $blog = Blog::find($validatedId['id']);
        if (!$blog) {
            return res_data('هذا المقال غير موجود', 'failed', 404);
        }
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'blogs');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'blogs' . '/' . $imageName;
            $data['image_cover'] = $data['image'];
        } else {
            unset($data['image']);
            unset($data['image_cover']);
        }

        $blog->update($data);
        return res_data($blog, 'تم التعديل بنجاح', 200);
    }

    public function deleteBlog(Request $request)
    {


        $validatedId = $request->validate([
            'id' => 'required',
        ]);

        $blog = Blog::find($validatedId['id']);
        if (!$blog) {
            return res_data('هذا المقال غير موجود', 'failed', 404);
        }
        $blog->delete();
        return res_data('تم الحذف بنجاح', 'sucess', 200);
    }

    public function showHideBlog(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:blogs,id',
            'hidden' => 'required|in:0,1',
        ]);

        $blog = Blog::find($data['id']);
        if (!$blog) {
            return res_data('هذا المقال غير موجود', 'failed', 404);
        }
        $blog->hidden = $data['hidden'];
        $blog->save();

        $message = $data['hidden'] == 0 ? 'تم إظهار المقال بنجاح' : 'تم إخفاء المقال بنجاح';
        return res_data($message, 'success', 200);
    }
}
