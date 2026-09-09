<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use Modules\Store\Http\Controllers\StoreController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('stores', StoreController::class)->names('store');
});

Route::prefix('user/store')->group(function () {
    Route::get('getStoreItems', [StoreController::class, 'getStoreItems']);
    Route::get('hasStoreCoupon', [StoreController::class, 'hasStoreCoupon']);
    Route::post('subscribe', [Modules\Store\Http\Controllers\SubscriptionController::class, 'subscribeToBooks']);
    Route::post('getMyLibrary', [Modules\Store\Http\Controllers\SubscriptionController::class, 'getMyLibrary']);
});

Route::middleware(AdminAuthentication::class)->group(function () {
    Route::prefix('admin/store')->group(function () {
        Route::get('getStoreItemsAdmin', [StoreController::class, 'getStoreItemsAdmin']);
        Route::post('addStoreItem', [StoreController::class, 'addStoreItem']);
        Route::post('updateStoreItem', [StoreController::class, 'updateStoreItem']);
        Route::post('deleteStoreItem', [StoreController::class, 'deleteStoreItem']);
        Route::post('uploadFile', [StoreController::class, 'uploadFile']);
    });
});
