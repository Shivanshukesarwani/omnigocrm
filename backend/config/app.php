<?php

return [
    'name' => env('APP_NAME', 'OmniGoCRM'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('CRM_TIMEZONE', 'Asia/Kolkata'),
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_IN',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
    'previous_keys' => [],
    'company_name' => env('CRM_COMPANY_NAME', 'Shivanshu Enterprises'),
];