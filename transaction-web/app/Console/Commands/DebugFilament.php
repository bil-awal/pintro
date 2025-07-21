<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class DebugFilament extends Command
{
    protected $signature = 'debug:filament';
    protected $description = 'Debug Filament panel registration and routes';

    public function handle()
    {
        $this->info('🔍 Debugging Filament Configuration...');
        $this->newLine();

        // Check if panels are registered
        $this->info('📋 Registered Panels:');
        try {
            $panels = app('filament')->getPanels();
            foreach ($panels as $panel) {
                $this->line("  - Panel ID: {$panel->getId()}");
                $this->line("    Path: {$panel->getPath()}");
                $this->line("    Auth Guard: {$panel->getAuthGuard()}");
                $this->line("    Login: " . ($panel->hasLogin() ? '✅' : '❌'));
            }
        } catch (\Exception $e) {
            $this->error("Error getting panels: {$e->getMessage()}");
        }

        $this->newLine();

        // Check routes
        $this->info('🛤️ Admin Routes:');
        $adminRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->uri, 'admin');
        });

        if ($adminRoutes->isEmpty()) {
            $this->error('❌ No admin routes found!');
        } else {
            foreach ($adminRoutes as $route) {
                $methods = implode('|', $route->methods);
                $this->line("  {$methods} /{$route->uri}");
            }
        }

        $this->newLine();

        // Check providers
        $this->info('📦 Service Providers:');
        $providers = app()->getLoadedProviders();
        $filamentProviders = array_filter(array_keys($providers), function ($provider) {
            return str_contains($provider, 'Filament') || str_contains($provider, 'AdminPanel');
        });

        foreach ($filamentProviders as $provider) {
            $this->line("  ✅ {$provider}");
        }

        $this->newLine();

        // Check admin guard
        $this->info('🔐 Auth Configuration:');
        $this->line("  Default Guard: " . config('auth.defaults.guard'));
        $this->line("  Admin Guard: " . (config('auth.guards.admin') ? '✅' : '❌'));
        $this->line("  Admin Provider: " . (config('auth.providers.admins') ? '✅' : '❌'));

        $this->newLine();

        // Check admin model
        $this->info('👤 Admin Model:');
        try {
            $adminCount = \App\Models\Admin::count();
            $this->line("  Admin users: {$adminCount}");
        } catch (\Exception $e) {
            $this->error("  Error: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('✅ Debug completed!');
    }
}
