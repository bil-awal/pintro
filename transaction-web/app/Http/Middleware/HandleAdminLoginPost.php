<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class HandleAdminLoginPost
{
    /**
     * Handle an incoming request.
     * This middleware intercepts POST requests to admin/login and handles authentication
     * when Filament fails to register the POST route properly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle POST requests to admin/login
        if ($request->isMethod('POST') && $request->is('admin/login')) {
            return $this->handleAdminLogin($request);
        }

        return $next($request);
    }

    private function handleAdminLogin(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Attempt authentication using admin guard
        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Update admin last login if method exists
            if ($admin = Auth::guard('admin')->user()) {
                if (method_exists($admin, 'updateLastLogin')) {
                    $admin->updateLastLogin();
                }
            }
            
            // Redirect to intended location or admin dashboard
            return redirect()->intended('/admin');
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->except('password'));
    }
}
