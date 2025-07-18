<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Services\GoTransactionService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                    
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),
            ]);
    }

    public function authenticate(): void
    {
        try {
            $data = $this->form->getState();
            
            $goService = app(GoTransactionService::class);
            $result = $goService->login([
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            if (!$result) {
                throw ValidationException::withMessages([
                    'data.email' => 'Invalid email or password.',
                ]);
            }

            // Store token and user data in session
            Session::put('user_token', $result['token']);
            Session::put('go_user_data', $result['user']);

            // Create or update user in local database
            $userData = $result['user'];
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

            // Log in the user
            Auth::login($user);

            // Redirect to intended page or dashboard
            redirect()->intended(filament()->getHomeUrl());

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'data.email' => 'Authentication failed. Please try again.',
            ]);
        }
    }
}
