<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
        'admin' => AdminAuthentication::class,
    ]);
        // A sent file that PHP did not fully receive -> stop with the reason (see the class).
        $middleware->append(\App\Http\Middleware\RejectFailedUploads::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The request is bigger than post_max_size: answer with the sizes instead of an empty 413.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            return \App\Support\Uploads::requestTooLarge($request)->render($request);
        });

        // Saving an uploaded file failed (disk full, no permission...): answer with the reason.
        $exceptions->render(function (\Symfony\Component\HttpFoundation\File\Exception\FileException $e, \Illuminate\Http\Request $request) {
            if (!$request->allFiles()) {
                return null; // not an upload (e.g. a download of a missing file): default handling
            }

            return \App\Support\Uploads::failureFrom($e)->render($request);
        });

        // Storage errors while saving an upload (e.g. the folder cannot be created).
        $exceptions->render(function (\League\Flysystem\FilesystemException $e, \Illuminate\Http\Request $request) {
            if (!$request->allFiles()) {
                return null;
            }

            return \App\Support\Uploads::failureFrom($e)->render($request);
        });

        // PHP hit max_execution_time while handling an upload: say it was a server timeout.
        $exceptions->render(function (\Symfony\Component\ErrorHandler\Error\FatalError $e, \Illuminate\Http\Request $request) {
            if (!$request->allFiles() || !($timeout = \App\Support\Uploads::timeoutFrom($e))) {
                return null;
            }

            return $timeout->render($request);
        });

        // Same for a PHP file warning while handling an upload (e.g. "mkdir(): Permission denied" before move()).
        $exceptions->render(function (\ErrorException $e, \Illuminate\Http\Request $request) {
            if (!$request->allFiles() || !preg_match('/^(mkdir|move_uploaded_file|rename|copy|file_put_contents|fopen|fwrite|chmod)\(\)/', $e->getMessage())) {
                return null;
            }

            return \App\Support\Uploads::failureFrom($e)->render($request);
        });
    })->create();
