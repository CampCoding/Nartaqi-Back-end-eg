<?php

use Illuminate\Support\Facades\Route;
 use App\Http\Middleware\AdminAuthentication;
use Modules\Admins\Http\Controllers\AdminsController;
use Modules\Admins\Http\Controllers\RolesController;
use Modules\Admins\Http\Controllers\PermissionsController;

Route::middleware([AdminAuthentication::class])->group(function () {
    Route::prefix('admins')->group(function () {
        Route::get('/list', [AdminsController::class, 'index']);
        Route::post('/show', [AdminsController::class, 'show']);
        Route::post('/store', [AdminsController::class, 'store']);
        Route::post('/update', [AdminsController::class, 'update']);
        Route::post('/delete', [AdminsController::class, 'destroy']);
        Route::post('/update_password', [AdminsController::class, 'updatePassword']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/list', [RolesController::class, 'index']);
        Route::post('/show', [RolesController::class, 'show']);
        Route::post('/store', [RolesController::class, 'store']);
        Route::post('/update', [RolesController::class, 'update']);
        Route::post('/delete', [RolesController::class, 'destroy']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/list', [PermissionsController::class, 'index']);
        Route::post('/show', [PermissionsController::class, 'show']);
        Route::post('/store', [PermissionsController::class, 'store']);
        Route::post('/update', [PermissionsController::class, 'update']);
        Route::post('/delete', [PermissionsController::class, 'destroy']);
    });
});


Route::prefix('admins')->group(function (): void {
    Route::post('/login', [AdminsController::class, 'login']);
});
