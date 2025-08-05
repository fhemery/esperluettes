<?php

return [
    // Shared services
    App\Domains\Shared\Providers\SharedServiceProvider::class,
    
    // Domain-specific providers
    App\Domains\Admin\Providers\AdminServiceProvider::class,
    App\Domains\Auth\Providers\AuthServiceProvider::class,
    
    // Add other domain providers here as they are created
];
