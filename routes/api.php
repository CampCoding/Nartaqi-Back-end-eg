<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\AuthenticationController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// Route::module('Admins');
// Signup route moved to routes/web.php to expose /authentication/signup without /api prefix

$modulesPath = base_path('Modules');

if (is_dir($modulesPath)) {
    foreach (glob($modulesPath . '/*/routes/api.php') as $routeFile) {
        require $routeFile;
    }
}