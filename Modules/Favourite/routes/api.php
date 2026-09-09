<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authentication;
use Modules\Favourite\Http\Controllers\FavouriteController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('favourites', FavouriteController::class)->names('favourite');
});


Route::middleware(Authentication::class)->group(function () {
    Route::prefix('user/favourite')->group(function () {
        Route::post('/user_favourites', [FavouriteController::class, 'user_favourites']);
        Route::post('/toggle_favourite', [FavouriteController::class, 'toggle_favourite']);
        
    });
});