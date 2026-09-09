<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admins\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthentication
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

        // Find admin by token
        $admin = Admin::where('token', $token)->first();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // التحقق من انتهاء صلاحية التوكن (التوكن ينتهي بعد شهر من إنشائه)
        $decoded = json_decode(base64_decode($admin->token), true);

        if (isset($decoded['exp']) && now()->timestamp > $decoded['exp']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expired',
            ], 401);
        }

        // Attach authenticated admin to the request
        $request->merge(['admin' => $admin]);
        $request->setUserResolver(fn () => $admin);

        return $next($request);

    }
}
