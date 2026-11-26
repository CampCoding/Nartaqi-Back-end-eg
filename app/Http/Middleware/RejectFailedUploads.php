<?php

namespace App\Http\Middleware;

use App\Support\Uploads;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Any sent file that PHP did not fully receive stops the request with the reason
 * (too big for the server, cut off mid-upload, server temp folder problem...).
 * Without this, many endpoints check hasFile() and silently carry on without the file.
 */
class RejectFailedUploads
{
    public function handle(Request $request, Closure $next)
    {
        foreach (Arr::dot($request->allFiles()) as $field => $file) {
            if ($file instanceof UploadedFile && ($error = Uploads::receiveError($file, (string) $field, $request))) {
                throw $error;
            }
        }

        return $next($request);
    }
}
