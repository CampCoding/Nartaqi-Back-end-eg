<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QrRedirectController;

Route::get('/', function () {
    return view('welcome');
});

// مسارات الدفع الديناميكية للكورسات
Route::get('/checkout/{round_id}/{student_id}', [PaymentController::class, 'showMethods'])->name('payment.checkout');
Route::get('/payment/initiate/{round_id}/{student_id}', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');

Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/fail', [PaymentController::class, 'paymentFail'])->name('payment.fail');

// Printed book QR codes. Public (no auth); no session/cookies so a scan stays a single cheap lookup.
Route::get('/q/{serial}', QrRedirectController::class)
    ->middleware('throttle:' . config('qr.per_minute') . ',1')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
    ])
    ->name('qr.redirect');

