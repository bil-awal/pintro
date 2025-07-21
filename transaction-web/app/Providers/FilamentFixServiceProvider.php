<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Response;

class FilamentFixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force disable CSP for all responses in development
        if ($this->app->environment('local', 'development')) {
            Response::macro('withoutCSP', function () {
                /** @var Response $this */
                $this->headers->remove('Content-Security-Policy');
                $this->headers->remove('Content-Security-Policy-Report-Only');
                $this->headers->remove('X-Content-Security-Policy');
                $this->headers->remove('X-WebKit-CSP');
                
                return $this;
            });
            
            // Apply to all responses
            $this->app['events']->listen('response', function ($response) {
                if (method_exists($response, 'withoutCSP')) {
                    $response->withoutCSP();
                }
            });
        }
        
        // Fix asset URLs
        if ($this->app->environment('local', 'development')) {
            $this->app['url']->forceRootUrl(config('app.url'));
            $this->app['url']->forceScheme('http');
        }
    }
}
