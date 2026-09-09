<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

// مسارات الدفع الديناميكية للكورسات
Route::get('/checkout/{round_id}/{student_id}', [PaymentController::class, 'showMethods'])->name('payment.checkout');
Route::get('/payment/initiate/{round_id}/{student_id}', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');

Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/fail', [PaymentController::class, 'paymentFail'])->name('payment.fail');

