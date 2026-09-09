<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use Modules\Home\Http\Controllers\HomeController;

// User / Public routes
Route::prefix('user/home')->group(function () {
    Route::get('/banners', [HomeController::class, 'getBanners']);
    Route::get('/video', [HomeController::class, 'getVideo']);
    Route::get('/', [HomeController::class, 'getHomeData']);
});

// Admin routes
Route::middleware(AdminAuthentication::class)->group(function () {
    Route::prefix('admin/home')->group(function () {
        Route::get('/banners', [HomeController::class, 'getAdminBanners']);
        Route::post('/banners/add', [HomeController::class, 'addBanner']);
        Route::post('/banners/delete', [HomeController::class, 'deleteBanner']);
        Route::get('/video', [HomeController::class, 'getAdminVideo']);
        Route::post('/video/update', [HomeController::class, 'updateVideo']);
    });
});

