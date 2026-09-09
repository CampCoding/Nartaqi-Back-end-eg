<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use App\Http\Middleware\MarketerAuthentication;
use Modules\Marketers\Http\Controllers\MarketersController;
use Modules\Marketers\Http\Controllers\AdminMarketerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('marketers', MarketersController::class)->names('marketers');
});


Route::prefix('user/marketers')->group(function () {
    Route::post('/apply', [MarketersController::class, 'applyMarketer']);
    Route::post('/login',[MarketersController::class, 'login']);
    Route::post('/send_verify_code',[MarketersController::class, 'sendCode']);
    Route::post('/verify_code',[MarketersController::class, 'verifyCode']);
});

Route::middleware(MarketerAuthentication::class)->group(function () {
    Route::prefix('user/marketers')->group(function () {
        Route::post('/profile',[MarketersController::class, 'getProfile']);
    });
});

Route::middleware(AdminAuthentication::class)->group(function () {
    
    Route::prefix('admin/marketers')->group(function () {
        Route::get('/', [AdminMarketerController::class, 'getMarketers']);
        Route::post('/generate_code', [AdminMarketerController::class, 'generateCode']);
    });
});