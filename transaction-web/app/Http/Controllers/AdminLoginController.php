<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class AdminLoginController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function login(Request $request)
    {
        // Validate the request
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Extract remember me
        $remember = $request->boolean('remember', false);

        // Attempt authentication with admin guard
        if (!Auth::guard('admin')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Regenerate session
        $request->session()->regenerate();

        // Log the login
        if ($admin = Auth::guard('admin')->user()) {
            $admin->update(['last_login_at' => now()]);
        }

        // Redirect to admin dashboard
        return redirect()->intended('/admin');
    }
}
