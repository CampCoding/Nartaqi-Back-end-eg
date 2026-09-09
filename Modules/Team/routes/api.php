<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use Modules\Team\Http\Controllers\TeamController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('teams', TeamController::class)->names('team');
});


Route::prefix('user/team')->group(function () {
    Route::get('/', [TeamController::class, 'getTeam']);
});

Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/team')->group(function () {
        Route::get('/', [TeamController::class, 'getAdminTeam']);
        Route::post('/add_member', [TeamController::class, 'addTeam']);
        Route::post('/update_member', [TeamController::class, 'updateTeam']);
        Route::post('/delete_member', [TeamController::class, 'deleteTeam']);
        Route::post('/show_member', [TeamController::class, 'showHideTeam']);
    });
});