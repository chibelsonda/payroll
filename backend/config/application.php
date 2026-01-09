<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination per page for API responses
    |
    */
    'pagination' => [
        'per_page' => env('PAGINATION_PER_PAGE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Token name for API authentication
    |
    */
    'auth' => [
        'token_name' => env('AUTH_TOKEN_NAME', 'API Token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee Settings
    |--------------------------------------------------------------------------
    |
    | Employee ID prefix for auto-generation
    |
    */
    'employee' => [
        'id_prefix' => env('EMPLOYEE_ID_PREFIX', 'EMP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Roles
    |--------------------------------------------------------------------------
    |
    | Available user roles in the system
    |
    */
    'roles' => [
        'admin' => 'admin',
        'employee' => 'employee',
    ],
];
