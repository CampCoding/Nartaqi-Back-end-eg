<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authentication;
use Modules\Cart\Http\Controllers\CartController;
use Modules\Authentication\Http\Controllers\AuthenticationController;


Route::middleware(Authentication::class)->group(function () {
    Route::prefix('user/cart')->group(function () {
        Route::get('/user_cart', [CartController::class, 'user_cart']);
        Route::post('/cart_toggle', [CartController::class, 'cart_toggle']);
        Route::post('/delete_cart_item', [CartController::class, 'delete_cart_item']);
        Route::post('delete_cart', [CartController::class, 'delete_cart']);
    });
});
