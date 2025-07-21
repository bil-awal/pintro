<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AdminGuest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $adminUser = session('admin_user');
        $isAuthenticated = session('admin_authenticated', false);

        // If admin is already authenticated, redirect to dashboard
        if ($isAuthenticated && $adminUser) {
            // Force HTTP scheme before redirect
            URL::forceScheme('http');
            
            // Generate dashboard URL manually to ensure HTTP
            $dashboardUrl = route('sysadmin.dashboard');
            
            // Double-check: replace https with http if somehow it's still there
            if (strpos($dashboardUrl, 'https://') === 0) {
                $dashboardUrl = str_replace('https://', 'http://', $dashboardUrl);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already authenticated',
                    'redirect_url' => $dashboardUrl
                ], 200);
            }

            return redirect($dashboardUrl);
        }

        return $next($request);
    }
}
