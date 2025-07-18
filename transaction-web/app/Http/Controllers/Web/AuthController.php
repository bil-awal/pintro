<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        // Redirect to dashboard if already logged in
        if (Session::has('user_token')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Show registration form.
     */
    public function showRegistrationForm()
    {
        // Redirect to dashboard if already logged in
        if (Session::has('user_token')) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            $credentials = [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ];

            $result = $this->goService->login($credentials);

            if (!$result) {
                return back()
                    ->withErrors(['login' => 'Invalid email or password'])
                    ->withInput($request->only('email'));
            }

            // Store user data and token in session
            Session::put('user_token', $result['token']);
            Session::put('user_data', $result['user']);
            Session::put('user_id', $result['user']['id']);

            Log::info('User logged in successfully', [
                'user_id' => $result['user']['id'],
                'email' => $credentials['email'],
            ]);

            return redirect()->route('dashboard')->with('success', 'Login successful!');

        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return back()
                ->withErrors(['login' => 'Login failed. Please try again.'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('name', 'email', 'phone'));
        }

        try {
            $userData = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'phone' => $request->input('phone'),
            ];

            $result = $this->goService->register($userData);

            if (!$result) {
                return back()
                    ->withErrors(['registration' => 'Registration failed. Email might already be taken.'])
                    ->withInput($request->only('name', 'email', 'phone'));
            }

            // Store user data and token in session
            Session::put('user_token', $result['token']);
            Session::put('user_data', $result['user']);
            Session::put('user_id', $result['user']['id']);

            Log::info('User registered successfully', [
                'user_id' => $result['user']['id'],
                'email' => $userData['email'],
            ]);

            return redirect()->route('dashboard')->with('success', 'Registration successful! Welcome!');

        } catch (\Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return back()
                ->withErrors(['registration' => 'Registration failed. Please try again.'])
                ->withInput($request->only('name', 'email', 'phone'));
        }
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        try {
            $token = Session::get('user_token');
            
            if ($token) {
                // Call Go service to invalidate token
                $this->goService->logout($token);
            }

            // Clear session data
            Session::forget(['user_token', 'user_data', 'user_id']);
            Session::flush();

            Log::info('User logged out successfully');

            return redirect()->route('login')->with('success', 'Logged out successfully!');

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
            ]);

            // Clear session anyway
            Session::forget(['user_token', 'user_data', 'user_id']);
            Session::flush();

            return redirect()->route('login')->with('success', 'Logged out successfully!');
        }
    }

    /**
     * Check if user is authenticated.
     */
    public function checkAuth(Request $request)
    {
        $token = Session::get('user_token');
        
        if (!$token) {
            return response()->json([
                'authenticated' => false,
                'message' => 'No token found',
            ]);
        }

        try {
            $result = $this->goService->verifyToken($token);
            
            if ($result) {
                return response()->json([
                    'authenticated' => true,
                    'user' => $result,
                ]);
            }

            // Token is invalid, clear session
            Session::forget(['user_token', 'user_data', 'user_id']);

            return response()->json([
                'authenticated' => false,
                'message' => 'Invalid token',
            ]);

        } catch (\Exception $e) {
            Log::error('Auth check error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'authenticated' => false,
                'message' => 'Auth check failed',
            ]);
        }
    }
}
