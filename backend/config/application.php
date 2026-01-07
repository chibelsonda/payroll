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
    | Student Settings
    |--------------------------------------------------------------------------
    |
    | Student ID prefix for auto-generation
    |
    */
    'student' => [
        'id_prefix' => env('STUDENT_ID_PREFIX', 'STU'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Settings
    |--------------------------------------------------------------------------
    |
    | Default credits for subjects
    |
    */
    'subject' => [
        'default_credits' => env('SUBJECT_DEFAULT_CREDITS', 3),
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
        'student' => 'student',
    ],
];
