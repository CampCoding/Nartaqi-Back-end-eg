<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authentication\Models\Student;
use Symfony\Component\HttpFoundation\Response;

class Authentication
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

        // Find student by token
        $student = Student::where('token', $token)->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // Check token expiry

        $decoded = json_decode(base64_decode($student->token), true);

        if (isset($decoded['exp']) && now()->timestamp > $decoded['exp']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expired',
            ], 401);
        }

        // Attach authenticated student to the request
        $request->setUserResolver(fn() => $student);

        return $next($request);
    }
}
