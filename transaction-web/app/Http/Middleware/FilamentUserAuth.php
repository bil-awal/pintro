<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Services\GoTransactionService;
use App\Models\User;

class FilamentUserAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user has token in session
        $token = Session::get('user_token');
        $userData = Session::get('go_user_data');
        
        if ($token && $userData) {
            try {
                // Verify token is still valid
                $goService = app(GoTransactionService::class);
                $verifiedData = $goService->verifyToken($token);
                
                if ($verifiedData) {
                    // Create or update user in local database
                    $user = User::updateOrCreate(
                        ['email' => $userData['email']],
                        [
                            'user_id' => $userData['id'],
                            'first_name' => $userData['name'] ?? '',
                            'email' => $userData['email'],
                            'phone' => $userData['phone'] ?? '',
                            'status' => 'active',
                        ]
                    );

                    // Manually log in the user
                    Auth::login($user);
                    
                    return $next($request);
                }
            } catch (\Exception $e) {
                // Token is invalid, clear session
                Session::forget(['user_token', 'go_user_data']);
            }
        }

        // Not authenticated, let Filament handle the redirect
        return $next($request);
    }
}
