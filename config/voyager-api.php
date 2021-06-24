<?php

return [

    /*
     * The config_key for voyager-api package.
     */
    'config_key' => env('VOYAGER_API_CONFIG_KEY', 'joy-voyager-api'),

    /*
     * The route_prefix for voyager-api package.
     */
    'route_prefix' => env('VOYAGER_API_ROUTE_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Controllers config
    |--------------------------------------------------------------------------
    |
    | Here you can specify voyager controller settings
    |
    */

    'controllers' => [
        'namespace' => 'Joy\\VoyagerApi\\Http\\Controllers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard config
    |--------------------------------------------------------------------------
    |
    | Here you can specify voyager api guard
    |
    */

    'guard' =>  env('VOYAGER_API_GUARD', 'api'),
];
