<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminAuthentication;
use App\Http\Middleware\Authentication;
use Modules\Admins\Models\Admin;
use Modules\Certificates\Http\Controllers\UserCertificatesController;
use Modules\Certificates\Http\Controllers\AdminCertificatesController;
use Modules\Certificates\Http\Controllers\CertificateApplicationsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('certificates', CertificateApplicationsController::class)->names('certificates');
});

Route::middleware(Authentication::class)->group(function () {
    Route::prefix('user/certificates')->group(function () {
        Route::post('/apply', [CertificateApplicationsController::class, 'applyForCertificate']);

        Route::post('/', [UserCertificatesController::class, 'userCertificates']);
    });
});

Route::middleware(AdminAuthentication::class)->group(function () {

    Route::prefix('admin/certificates')->group(function () {
        Route::post('/generate', [AdminCertificatesController::class, 'generateCertificate']);
        Route::post('/applications', [AdminCertificatesController::class, 'getApplications']);
        Route::post('/upload_pdf', [AdminCertificatesController::class, 'uploadCertificatePdf']);
        Route::post('/edit_certificate',[AdminCertificatesController::class, 'editCertificate'] );
        Route::post('/delete_certificate',[AdminCertificatesController::class, 'deleteCertificate'] );
    });
});
