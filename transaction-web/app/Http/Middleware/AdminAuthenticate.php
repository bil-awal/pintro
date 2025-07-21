<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = 'admin')
    {
        // Check if admin is authenticated
        if (!Auth::guard($guard)->check()) {
            Log::warning('Unauthenticated admin access attempt', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login to continue.',
                    'error' => [
                        'code' => 401,
                        'message' => 'Authentication required'
                    ]
                ], 401);
            }

            return redirect()->guest(route('admin.login'))->with('error', 'Please login to access this page.');
        }

        // Check if admin is active
        $admin = Auth::guard($guard)->user();
        if ($admin && !$admin->isActive()) {
            Log::warning('Blocked admin access attempt', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'status' => $admin->status,
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);

            Auth::guard($guard)->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                    'error' => [
                        'code' => 403,
                        'message' => 'Account deactivated'
                    ]
                ], 403);
            }

            return redirect()->route('admin.login')->with('error', 'Your account has been deactivated. Please contact support.');
        }

        return $next($request);
    }
}
