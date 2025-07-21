<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Services\GoTransactionService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    protected static string $view = 'filament.user.pages.auth.login';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('email')
                ->label('Emai')
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

    public function authenticate(): LoginResponse
    {
        $data = $this->form->getState();

        // 1) Kirim kredensial ke Go-Transaction Service
        $result = app(GoTransactionService::class)->login([
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        dd('RESULT' . $result);

        // 2) Tangani gagal autentikasi
        if (! $result || empty($result['token']) || empty($result['user'])) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        // 3) Simpan token & user data dari Go ke session
        Session::put('user_token', $result['token']);
        Session::put('go_user_data', $result['user']);

        // 4) Sinkronisasi ke local database
        $userData = $result['user'];
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'user_id'    => $userData['id'],
                'first_name' => $userData['name']  ?? '',
                'phone'      => $userData['phone'] ?? '',
                'status'     => 'active',
            ]
        );

        // 5) Login via Filament guard
        Auth::guard(config('filament.auth.guard'))->login($user);

        // 6) Redirect balik ke dashboard Filament
        return app(LoginResponse::class);
    }
}
