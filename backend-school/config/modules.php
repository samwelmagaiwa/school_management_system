<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Service Providers
    |--------------------------------------------------------------------------
    |
    | Here you can register all module service providers that should be
    | automatically loaded by the application.
    |
    */

    'providers' => [
        App\Modules\User\UserServiceProvider::class,
        // Add other module service providers here
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Routes
    |--------------------------------------------------------------------------
    |
    | Define the route prefixes and middleware for each module.
    |
    */

    'routes' => [
        'user' => [
            'prefix' => 'api',
            'middleware' => ['api'],
        ],
    ],
];