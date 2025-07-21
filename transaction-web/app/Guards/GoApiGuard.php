<?php

namespace App\Guards;

use App\Services\GoTransactionService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Admin;

class GoApiGuard implements Guard
{
    use GuardHelpers;

    protected $request;
    protected $goService;
    protected $user;

    public function __construct(UserProvider $provider, Request $request, GoTransactionService $goService)
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->goService = $goService;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();
        
        if (!$token) {
            return null;
        }

        try {
            // Verify token with Go API
            $userData = $this->goService->verifyToken($token);
            
            if ($userData) {
                // Create a mock Admin model with Go API user data
                $this->user = new Admin([
                    'id' => $userData['id'],
                    'email' => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'phone' => $userData['phone'] ?? null,
                    'status' => $userData['status'] ?? 'active',
                ]);
                
                $this->user->exists = true;
                $this->user->setAttribute('token', $token);
                
                return $this->user;
            }
        } catch (\Exception $e) {
            Log::error('Error verifying token with Go API', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        try {
            $response = $this->goService->login([
                'email' => $credentials['email'],
                'password' => $credentials['password']
            ]);

            return $response !== null;
        } catch (\Exception $e) {
            Log::error('Error validating credentials with Go API', [
                'email' => $credentials['email'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        try {
            $response = $this->goService->login([
                'email' => $credentials['email'],
                'password' => $credentials['password']
            ]);

            if ($response && isset($response['token']) && isset($response['user'])) {
                // Store token in session
                session(['admin_token' => $response['token']]);
                session(['admin_user' => $response['user']]);
                
                // Create mock admin user
                $this->user = new Admin($response['user']);
                $this->user->exists = true;
                $this->user->setAttribute('token', $response['token']);
                
                Log::info('Admin login successful', [
                    'admin_id' => $response['user']['id'],
                    'email' => $credentials['email']
                ]);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Error attempting login with Go API', [
                'email' => $credentials['email'],
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Log a user into the application without sessions or cookies.
     */
    public function once(array $credentials = [])
    {
        return $this->attempt($credentials);
    }

    /**
     * Log the user out of the application.
     */
    public function logout()
    {
        $token = session('admin_token');
        
        if ($token) {
            try {
                $this->goService->logout($token);
                
                Log::info('Admin logout successful', [
                    'admin_id' => session('admin_user.id'),
                    'email' => session('admin_user.email')
                ]);
            } catch (\Exception $e) {
                Log::error('Error during logout with Go API', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Clear session data
        session()->forget(['admin_token', 'admin_user']);
        $this->user = null;
    }

    /**
     * Get the token for the current request.
     */
    protected function getTokenForRequest()
    {
        // First check session (for web admin)
        $token = session('admin_token');
        
        if ($token) {
            return $token;
        }

        // Then check Authorization header (for API requests)
        $header = $this->request->header('Authorization', '');
        
        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        // Check query parameter
        return $this->request->query('token');
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $user->id;
        }
    }

    /**
     * Set the current user.
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get the user provider used by the guard.
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;
    }
}
