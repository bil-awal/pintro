<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        // Handle reverse proxy headers for proper URL generation
        // Only force scheme if explicitly set to HTTPS and not disabled by environment
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' && !env('FORCE_HTTPS', false) === false) {
            URL::forceScheme($_SERVER['HTTP_X_FORWARDED_PROTO']);
        }
        
        // Force root URL to nginx proxy (without port 8000)
        URL::forceRootUrl(config('app.url'));
        
        // Only force HTTPS in production if explicitly enabled and not disabled
        if ($this->app->environment('production') 
            && !$this->app->runningInConsole() 
            && env('FORCE_HTTPS', false) === true 
            && env('APP_FORCE_HTTPS', false) === true) {
            URL::forceScheme('https');
        }
        
        // Force HTTP for local development and when HTTPS is disabled
        if ($this->app->environment('local', 'development') 
            || env('FORCE_HTTPS', false) === false 
            || env('APP_FORCE_HTTPS', false) === false) {
            URL::forceScheme('http');
            $this->app['url']->forceRootUrl(config('app.url'));
        }

        // Register custom user provider for Go service authentication
        Auth::provider('go_service', function ($app, array $config) {
            return new GoServiceUserProvider($app->make(GoTransactionService::class));
        });
    }
}
