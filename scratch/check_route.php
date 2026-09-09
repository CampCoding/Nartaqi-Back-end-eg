<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    if (str_contains($route->uri(), 'getExamSummaryReport')) {
        echo implode(',', $route->methods()) . " -> " . $route->uri() . " (" . $route->getActionName() . ")\n";
    }
}
