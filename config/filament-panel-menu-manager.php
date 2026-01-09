<?php

// config/filament-menu-manager.php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy support. When enabled, each tenant will have
    | their own set of menus.
    |
    */
    'multi_tenancy' => [
        'enabled' => false,
        'tenant_attribute' => 'company_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Pages
    |--------------------------------------------------------------------------
    |
    | List of page classes to exclude from route discovery.
    | These pages won't appear in the menu builder dropdown.
    |
    */
    'exclude_pages' => [
        // \App\Filament\Pages\SecretPage::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Permission configuration for menu filtering.
    | Shield integration is optional - if disabled or Shield is not installed,
    | all menu items will be visible to all users (no permission filtering).
    |
    */
    'permissions' => [
        'shield_integration' => true, // Set to false to disable Shield integration
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache configuration for menu queries.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => null, // null = forever, or specify seconds
        'prefix' => 'menus_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Define who can access the menu builder.
    |
    */
    'access' => [
        'attribute' => 'is_admin', // User attribute to check
        'value' => true,           // Expected value
    ],
];
