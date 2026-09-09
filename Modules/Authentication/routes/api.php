<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authentication;
use Modules\Authentication\Http\Controllers\AuthenticationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authentications', AuthenticationController::class)->names('authentication');
});



Route::prefix('authentication')->group(function (): void {

    Route::post('send-code', [AuthenticationController::class, 'sendCode']);
    Route::post('verify-code', [AuthenticationController::class, 'verifyCode']);
    Route::post('signup', [AuthenticationController::class, 'signUp']);
    Route::post('login', [AuthenticationController::class, 'login']);

    // Forgot password
    Route::post('forgot/send-code', [AuthenticationController::class, 'forgotSendCode']);
    Route::post('forgot/verify-code', [AuthenticationController::class, 'forgotVerifyCode']);
    Route::post('forgot/reset', [AuthenticationController::class, 'resetPassword']);

});

Route::middleware(Authentication::class)->group(function () {
    Route::prefix('authentication')->group(function (): void {

        // Change password
        Route::post('change_password', [AuthenticationController::class, 'changePassword']);

        // Student info
        Route::post('student_info', [AuthenticationController::class, 'studentInfo']);
        Route::post('update_student_info', [AuthenticationController::class, 'updateStudentInfo']);
    });
});