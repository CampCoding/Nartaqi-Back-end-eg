<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use Modules\Faqs\Http\Controllers\FaqsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('faqs', FaqsController::class)->names('faqs');
});

Route::prefix('user/faqs')->group(function () {
    Route::get('/', [FaqsController::class, 'getFaq']);
});
Route::prefix('user/complaints')->group(function () {
    Route::get('/', [FaqsController::class, 'getAdmincomplaints']);
    Route::get('/getUserComplaints', [FaqsController::class, 'getUserComplaints']);
});


Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/faqs')->group(function () {
        Route::get('/', [FaqsController::class, 'getAdminFaq']);
        Route::post('/add_faq', [FaqsController::class, 'addFaq']);
        Route::post('/update_faq', [FaqsController::class, 'updateFaq']);
        Route::post('/delete_faq', [FaqsController::class, 'deleteFaq']);
        Route::post('/show_faq', [FaqsController::class, 'showHideFaq']);
    });

    Route::prefix('admin/complaints')->group(function () {
        Route::get('/', [FaqsController::class, 'getAdmincomplaints']);
        Route::post('/addcomplaints', [FaqsController::class, 'addcomplaints']);
        Route::post('/updatecomplaints', [FaqsController::class, 'updatecomplaints']);
        Route::post('/delete_faq', [FaqsController::class, 'deleteFaq']);
        Route::post('/showHideComplaints', [FaqsController::class, 'showHideComplaints']);
    });
});
