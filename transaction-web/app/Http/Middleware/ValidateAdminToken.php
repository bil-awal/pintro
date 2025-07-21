<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ValidateAdminToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin && !$admin->hasValidToken()) {
            Log::warning('Admin with invalid token attempted access', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);

            Auth::guard('admin')->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token. Please login again.',
                    'error' => [
                        'code' => 401,
                        'message' => 'Token invalid'
                    ]
                ], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Your session has expired. Please login again.');
        }

        return $next($request);
    }
}
