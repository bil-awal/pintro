<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ForceAdminLoginProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force register POST admin/login route
        $this->registerAdminLoginRoute();
    }

    private function registerAdminLoginRoute(): void
    {
        Route::middleware(['web'])->group(function () {
            Route::post('admin/login', [\App\Http\Controllers\AdminLoginController::class, 'login'])
                ->name('filament.admin.auth.login.post');
        });
    }
}
