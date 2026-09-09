<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authentication;
use App\Http\Middleware\AdminAuthentication;
use Modules\Badges\Http\Controllers\BadgesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('badges', BadgesController::class)->names('badges');
});

Route::middleware(Authentication::class)->group(function () {
    Route::prefix('user/badges')->group(function () {
        Route::post('/', [BadgesController::class, 'getStudentBadges']);
    });
});

Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/badges')->group(function () {
        Route::get('/', [BadgesController::class, 'getAllBadges']);
        Route::post('/student_badges', [BadgesController::class, 'getStudentBadgesByAdmin']);
        Route::post('/create_badge', [BadgesController::class, 'createBadge']);
        Route::post('/update_badge', [BadgesController::class, 'updateBadge']);
        Route::post('/delete_badge', [BadgesController::class, 'deleteBadge']);
        Route::post('/assign_badge_to_student',[BadgesController::class, 'assignBadgeToStudent']);
    });

});
