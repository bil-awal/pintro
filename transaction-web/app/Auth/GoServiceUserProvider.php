<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GoServiceUserProvider implements UserProvider
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier)
    {
        return User::find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = User::find($identifier);
        
        if ($user && $user->getRememberToken() === $token) {
            return $user;
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['email'])) {
            return null;
        }

        try {
            // Try to authenticate with Go service
            $result = $this->goService->login([
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);

            if (!$result) {
                return null;
            }

            // Create or update user in local database
            $userData = $result['user'];
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'user_id' => $userData['id'],
                    'first_name' => $userData['name'] ?? '',
                    'last_name' => '',
                    'email' => $userData['email'],
                    'phone' => $userData['phone'] ?? '',
                    'status' => 'active',
                    'password' => Hash::make($credentials['password']), // Store for fallback
                ]
            );

            // Store JWT token in session for later use
            session(['user_token' => $result['token']]);
            session(['go_user_data' => $userData]);

            return $user;

        } catch (\Exception $e) {
            Log::error('Go service authentication error', [
                'error' => $e->getMessage(),
                'email' => $credentials['email'],
            ]);

            return null;
        }
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // For Go service, credentials are already validated in retrieveByCredentials
        return true;
    }

    /**
     * Rehash the user's password if required and supported.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        // Not applicable for Go service authentication
    }

    /**
     * Get the Eloquent user model.
     */
    public function getModel()
    {
        return User::class;
    }

    /**
     * Set the Eloquent user model.
     */
    public function setModel($model)
    {
        // Not needed for this implementation
    }
}
