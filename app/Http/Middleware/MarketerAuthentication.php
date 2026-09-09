<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Marketers\Models\Marketer;
use Symfony\Component\HttpFoundation\Response;

class MarketerAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is required',
            ], 401);
        }

        // If token starts with "Bearer ", remove it
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // Find marketer by token
        $marketer = Marketer::where('token', $token)->first();

        if (!$marketer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // Check token expiry

        $decoded = json_decode(base64_decode($marketer->token), true);

        if (isset($decoded['exp']) && now()->timestamp > $decoded['exp']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expired',
            ], 401);
        }

        // Attach authenticated marketer to the request
        $request->merge(['marketer' => $marketer]);
        $request->setUserResolver(fn () => $marketer);

        return $next($request);

    }
}
