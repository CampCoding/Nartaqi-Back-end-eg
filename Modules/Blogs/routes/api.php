<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use App\Http\Middleware\Authentication;
use Modules\Blogs\Http\Controllers\BlogsController;
use Modules\Blogs\Http\Controllers\AdminBlogsController;
use Modules\Blogs\Http\Controllers\BlogCommentsController;
use Modules\Blogs\Http\Controllers\AdminBlogCommentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('blogs', BlogsController::class)->names('blogs');
});

Route::prefix('user/blogs')->group(function () {
    Route::get('/', [BlogsController::class, 'getBlogs']);
    Route::post('/get_blog', [BlogsController::class, 'getBlog']);

    Route::post('/comments', [BlogCommentsController::class, 'getBlogComments']);

    Route::middleware(Authentication::class)->group(function () {
        Route::post('/add_comment', [BlogCommentsController::class, 'addComment']);
    });
});

Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/blogs')->group(function () {
        Route::get('/', [AdminBlogsController::class, 'getBlogsAdmin']);
        Route::post('/add_blog', [AdminBlogsController::class, 'addBlog']);
        Route::post('/update_blog', [AdminBlogsController::class, 'updateBlog']);
        Route::post('/delete_blog', [AdminBlogsController::class, 'deleteBlog']);
        Route::post('/show_blog', [AdminBlogsController::class, 'showHideBlog']);

        Route::post('/comments', [AdminBlogCommentsController::class, 'getBlogComments']);
        Route::post('/delete_comment', [AdminBlogCommentsController::class, 'deleteComment']);
        Route::post('/show_comment', [AdminBlogCommentsController::class, 'showHideComment']);
    });

});
