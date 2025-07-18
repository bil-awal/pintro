<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\GoServiceUserProvider;
use App\Services\GoTransactionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom user provider for Go service authentication
        Auth::provider('go_service', function ($app, array $config) {
            return new GoServiceUserProvider($app->make(GoTransactionService::class));
        });
    }
}
