<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class AdminAuthController extends Controller
{
    protected $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Force HTTP scheme for all redirects in this controller
     */
    private function forceHttpRedirect($routeName, $message = null, $messageType = 'success')
    {
        // Force HTTP scheme
        URL::forceScheme('http');
        
        // Generate URL manually to ensure HTTP
        $url = route($routeName);
        
        // Double-check: replace https with http if somehow it's still there
        if (strpos($url, 'https://') === 0) {
            $url = str_replace('https://', 'http://', $url);
        }

        $redirect = redirect($url);
        
        if ($message) {
            $redirect = $redirect->with($messageType, $message);
        }
        
        return $redirect;
    }

    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Handle admin login request.
     */
    public function login(Request $request)
    {
        // Force HTTP scheme at the beginning
        URL::forceScheme('http');
        
        Log::info('Admin login attempt started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            Log::warning('Admin login validation failed', [
                'email' => $request->input('email'),
                'errors' => $validator->errors()->toArray()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return back()->withErrors($validator)->withInput($request->except('password'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        try {
            // Debug: Log Go API call attempt
            Log::info('Attempting Go API login', [
                'email' => $credentials['email'],
                'go_service_url' => config('app.go_transaction_service_url', env('GO_TRANSACTION_SERVICE_URL'))
            ]);

            // Test Go API health first
            try {
                $healthCheck = $this->goService->getSystemHealth();
                Log::info('Go API health check', ['health' => $healthCheck]);
                
                if (!$healthCheck || $healthCheck['status'] !== 'healthy') {
                    throw new \Exception('Go API is not healthy: ' . json_encode($healthCheck));
                }
            } catch (\Exception $e) {
                Log::error('Go API health check failed', [
                    'error' => $e->getMessage(),
                    'go_service_url' => env('GO_TRANSACTION_SERVICE_URL')
                ]);
                
                return back()->withErrors([
                    'email' => 'Authentication service is currently unavailable. Please try again later.'
                ])->withInput($request->except('password'));
            }

            // Login directly through Go API service
            $response = $this->goService->login([
                'email' => $credentials['email'],
                'password' => $credentials['password']
            ]);

            Log::info('Go API login response', [
                'email' => $credentials['email'],
                'response_received' => !is_null($response),
                'has_token' => isset($response['token']),
                'has_user' => isset($response['user']),
                'response_keys' => $response ? array_keys($response) : []
            ]);

            if ($response && isset($response['token']) && isset($response['user'])) {
                // Store admin session data
                session([
                    'admin_token' => $response['token'],
                    'admin_user' => $response['user'],
                    'admin_authenticated' => true
                ]);
                
                // Debug: Log successful session storage
                Log::info('Admin session stored successfully', [
                    'admin_id' => $response['user']['id'],
                    'email' => $credentials['email'],
                    'session_id' => session()->getId(),
                    'token_length' => strlen($response['token'])
                ]);

                Log::info('Admin login successful', [
                    'admin_id' => $response['user']['id'],
                    'email' => $credentials['email'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                $userName = $response['user']['first_name'] . ' ' . $response['user']['last_name'];

                if ($request->expectsJson()) {
                    // Force HTTP in JSON response URL too
                    $dashboardUrl = route('sysadmin.dashboard');
                    if (strpos($dashboardUrl, 'https://') === 0) {
                        $dashboardUrl = str_replace('https://', 'http://', $dashboardUrl);
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'admin' => $response['user'],
                            'token' => $response['token'],
                            'redirect_url' => $dashboardUrl
                        ]
                    ]);
                }
                
                // Debug: Log redirect attempt
                Log::info('Redirecting to dashboard', [
                    'admin_id' => $response['user']['id']
                ]);
                
                // Use custom redirect method to force HTTP
                return $this->forceHttpRedirect('sysadmin.dashboard', 'Welcome back, ' . $userName . '!');
            }

            // Login failed
            Log::warning('Admin login failed - invalid credentials or API response', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'api_response' => $response
            ]);

            $errorMessage = 'Invalid email or password. Please check your credentials and try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => [
                        'code' => 401,
                        'message' => 'Authentication failed'
                    ]
                ], 401);
            }

            return back()->withErrors([
                'email' => $errorMessage,
            ])->withInput($request->except('password'));

        } catch (\Exception $e) {
            Log::error('Admin login error', [
                'email' => $credentials['email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $errorMessage = 'Login service is temporarily unavailable. Please try again later.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => [
                        'code' => 500,
                        'message' => 'Service unavailable'
                    ]
                ], 500);
            }

            return back()->withErrors([
                'email' => $errorMessage,
            ])->withInput($request->except('password'));
        }
    }

    /**
     * Handle admin logout request.
     */
    public function logout(Request $request)
    {
        try {
            $adminUser = session('admin_user');
            $token = session('admin_token');
            
            if ($adminUser) {
                Log::info('Admin logout initiated', [
                    'admin_id' => $adminUser['id'],
                    'email' => $adminUser['email'],
                    'ip' => $request->ip()
                ]);
            }

            // Logout through Go API if token exists
            if ($token) {
                try {
                    $this->goService->logout($token);
                } catch (\Exception $e) {
                    Log::warning('Go API logout failed', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Clear admin session data
            session()->forget(['admin_token', 'admin_user', 'admin_authenticated']);
            
            // Invalidate the session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                // Force HTTP in JSON response URL too
                $loginUrl = route('sysadmin.login');
                if (strpos($loginUrl, 'https://') === 0) {
                    $loginUrl = str_replace('https://', 'http://', $loginUrl);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Logout successful',
                    'redirect_url' => $loginUrl
                ]);
            }

            return $this->forceHttpRedirect('sysadmin.login', 'You have been logged out successfully.');

        } catch (\Exception $e) {
            Log::error('Admin logout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Logout failed. Please try again.',
                    'error' => [
                        'code' => 500,
                        'message' => 'Service error'
                    ]
                ], 500);
            }

            return $this->forceHttpRedirect('sysadmin.login', 'Logout failed. Please try again.', 'error');
        }
    }

    /**
     * Get admin profile information.
     */
    public function profile(Request $request)
    {
        try {
            $adminUser = session('admin_user');
            $token = session('admin_token');
            
            if (!$adminUser || !session('admin_authenticated')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'error' => [
                            'code' => 401,
                            'message' => 'No authenticated admin found'
                        ]
                    ], 401);
                }
                
                return $this->forceHttpRedirect('sysadmin.login');
            }

            // Get fresh profile data from Go API
            if ($token) {
                $profileData = $this->goService->getUserProfile($token);
                if ($profileData) {
                    // Update session with fresh data
                    session(['admin_user' => $profileData]);
                    $adminUser = $profileData;
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile retrieved successfully',
                    'data' => [
                        'admin' => $adminUser
                    ]
                ]);
            }

            return view('admin.profile.show', compact('adminUser'));

        } catch (\Exception $e) {
            Log::error('Admin profile error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve profile',
                    'error' => [
                        'code' => 500,
                        'message' => 'Service error'
                    ]
                ], 500);
            }

            return back()->with('error', 'Failed to load profile. Please try again.');
        }
    }

    /**
     * Update admin profile.
     */
    public function updateProfile(Request $request)
    {
        try {
            $adminUser = session('admin_user');
            $token = session('admin_token');
            
            if (!$adminUser || !session('admin_authenticated') || !$token) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated'
                    ], 401);
                }
                
                return $this->forceHttpRedirect('sysadmin.login');
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'phone' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return back()->withErrors($validator)->withInput();
            }

            $updateData = $request->only(['first_name', 'last_name', 'phone']);

            $updatedProfile = $this->goService->updateUserProfile($token, $updateData);
            
            if ($updatedProfile) {
                // Update session with fresh data
                session(['admin_user' => $updatedProfile]);

                Log::info('Admin profile updated successfully', [
                    'admin_id' => $adminUser['id'],
                    'updated_fields' => array_keys($updateData)
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Profile updated successfully',
                        'data' => [
                            'admin' => $updatedProfile
                        ]
                    ]);
                }

                return back()->with('success', 'Profile updated successfully!');
            }

            $errorMessage = 'Failed to update profile. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Admin profile update error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'ip' => $request->ip()
            ]);

            $errorMessage = 'Failed to update profile. Please try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => [
                        'code' => 500,
                        'message' => 'Service error'
                    ]
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Check authentication status.
     */
    public function check(Request $request)
    {
        $adminUser = session('admin_user');
        $isAuthenticated = session('admin_authenticated', false);
        
        return response()->json([
            'authenticated' => $isAuthenticated && !is_null($adminUser),
            'admin' => $adminUser
        ]);
    }

    /**
     * Get system health status from Go API.
     */
    public function systemHealth(Request $request)
    {
        try {
            $health = $this->goService->getSystemHealth();
            
            return response()->json([
                'success' => true,
                'message' => 'System health retrieved successfully',
                'data' => $health
            ]);

        } catch (\Exception $e) {
            Log::error('System health check error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system health',
                'error' => [
                    'code' => 500,
                    'message' => 'Service error'
                ]
            ], 500);
        }
    }

    /**
     * Debug endpoint for testing authentication
     */
    public function debug(Request $request)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        try {
            $debugInfo = [
                'environment' => app()->environment(),
                'session_data' => [
                    'admin_user' => session('admin_user'),
                    'admin_token' => session('admin_token') ? 'exists' : 'missing',
                    'admin_authenticated' => session('admin_authenticated'),
                    'session_id' => session()->getId()
                ],
                'go_api_config' => [
                    'url' => env('GO_TRANSACTION_SERVICE_URL'),
                    'api_key' => env('GO_TRANSACTION_API_KEY') ? 'exists' : 'missing'
                ]
            ];

            // Test Go API health
            try {
                $health = $this->goService->getSystemHealth();
                $debugInfo['go_api_health'] = $health;
            } catch (\Exception $e) {
                $debugInfo['go_api_health'] = ['error' => $e->getMessage()];
            }

            return response()->json($debugInfo);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
