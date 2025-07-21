<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
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

            return redirect()->route('admin.login');
        }

        // For now, we'll check if admin is active
        // In the future, you can implement role-based access control
        if (!$admin->isActive()) {
            Log::warning('Inactive admin attempted role-based access', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'required_role' => $role,
                'admin_status' => $admin->status,
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Insufficient permissions.',
                    'error' => [
                        'code' => 403,
                        'message' => 'Insufficient permissions'
                    ]
                ], 403);
            }

            return back()->with('error', 'Access denied. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
