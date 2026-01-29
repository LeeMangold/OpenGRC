<?php

return [
    'name' => 'DataManager',

    /*
    |--------------------------------------------------------------------------
    | Module Enable/Disable
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable the DataManager module entirely.
    | When disabled, the import/export pages will not be registered.
    |
    */
    'enabled' => env('MODULE_DATAMANAGER_ENABLED', true), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */
    'export' => [
        'chunk_size' => 1000,
        'default_disk' => 'private',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    */
    'import' => [
        'chunk_size' => 100,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mimes' => ['text/csv', 'application/csv', 'text/plain'],
        'error_threshold' => 0.1, // Rollback if > 10% errors
    ],
];
