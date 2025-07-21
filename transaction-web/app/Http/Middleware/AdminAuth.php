<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $adminUser = session('admin_user');
        $isAuthenticated = session('admin_authenticated', false);
        $token = session('admin_token');

        // Check if admin is authenticated
        if (!$isAuthenticated || !$adminUser || !$token) {
            Log::warning('Unauthenticated admin access attempt', [
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => [
                        'code' => 401,
                        'message' => 'Authentication required'
                    ]
                ], 401);
            }

            // Force HTTP scheme before redirect
            URL::forceScheme('http');
            
            // Generate login URL manually to ensure HTTP
            $loginUrl = route('sysadmin.login');
            
            // Double-check: replace https with http if somehow it's still there
            if (strpos($loginUrl, 'https://') === 0) {
                $loginUrl = str_replace('https://', 'http://', $loginUrl);
            }

            return redirect($loginUrl)->with('error', 'Please login to access admin panel.');
        }

        // Add admin data to request for easy access in controllers
        $request->attributes->set('admin_user', $adminUser);
        $request->attributes->set('admin_token', $token);

        return $next($request);
    }
}
