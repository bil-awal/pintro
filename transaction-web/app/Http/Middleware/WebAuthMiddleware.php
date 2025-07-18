<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Log;

class WebAuthMiddleware
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user has token in session
        $token = Session::get('user_token');
        
        if (!$token) {
            return $this->redirectToLogin($request, 'Please login to continue.');
        }

        try {
            // Verify token with Go service
            $userData = $this->goService->verifyToken($token);
            
            if (!$userData) {
                // Token is invalid, clear session and redirect
                Session::forget(['user_token', 'user_data', 'user_id']);
                return $this->redirectToLogin($request, 'Your session has expired. Please login again.');
            }

            // Update session with fresh user data
            Session::put('user_data', $userData);
            Session::put('user_id', $userData['id']);

            // Add user data to request for controllers
            $request->attributes->set('authenticated_user', $userData);
            $request->attributes->set('user_token', $token);

        } catch (\Exception $e) {
            Log::error('Authentication middleware error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            // On error, clear session and redirect
            Session::forget(['user_token', 'user_data', 'user_id']);
            return $this->redirectToLogin($request, 'Authentication failed. Please login again.');
        }

        return $next($request);
    }

    /**
     * Redirect to login with appropriate message.
     */
    private function redirectToLogin(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('login'),
            ], 401);
        }

        return redirect()->route('login')
            ->with('error', $message);
    }
}
