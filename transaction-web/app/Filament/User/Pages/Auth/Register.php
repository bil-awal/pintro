<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Services\GoTransactionService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                    
                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email')
                    ->extraInputAttributes(['tabindex' => 2]),
                    
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20)
                    ->extraInputAttributes(['tabindex' => 3]),
                    
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(6)
                    ->extraInputAttributes(['tabindex' => 4]),
                    
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required()
                    ->same('password')
                    ->extraInputAttributes(['tabindex' => 5]),
            ]);
    }

    public function register(): ?\Filament\Http\Responses\Auth\Contracts\RegistrationResponse
    {
        try {
            $data = $this->form->getState();
            
            $goService = app(GoTransactionService::class);
            $result = $goService->register([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'password' => $data['password'],
            ]);

            if (!$result) {
                throw ValidationException::withMessages([
                    'data.email' => 'Registration failed. Email might already be taken.',
                ]);
            }

            // Store token and user data in session
            Session::put('user_token', $result['token']);
            Session::put('go_user_data', $result['user']);

            // Create user in local database
            $userData = $result['user'];
            $user = User::create([
                'user_id' => $userData['id'],
                'first_name' => $userData['name'] ?? $data['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? $data['phone'],
                'status' => 'active',
            ]);

            // Log in the user
            Auth::login($user);

            // Return redirect response
            return redirect()->intended(filament()->getHomeUrl());

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'data.email' => 'Registration failed. Please try again.',
            ]);
        }
    }
}
