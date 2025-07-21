<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\CustomAuthServiceProvider::class,
    // App\Providers\ForceAdminLoginProvider::class, // DISABLED - conflicts with sys-admin
    // App\Providers\Filament\AdminPanelProvider::class, // DISABLED - causes HTTPS redirect
    App\Providers\Filament\UserPanelProvider::class,
];
