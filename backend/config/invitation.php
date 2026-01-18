<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invitation Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for invitation emails including branding and styling.
    |
    */

    'email' => [
        // Company logo URL (can be absolute or relative)
        'logo_url' => env('INVITATION_LOGO_URL', '/images/logo.png'),

        // Primary color for email template (hex code)
        'primary_color' => env('INVITATION_PRIMARY_COLOR', '#1976D2'),

        // Company name (fallback if not provided)
        'company_name' => env('APP_NAME', 'Payroll System'),

        // Frontend URL for invitation acceptance
        'accept_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/accept-invitation',
    ],
];
