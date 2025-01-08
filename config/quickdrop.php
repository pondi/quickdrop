<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    |
    | These settings apply to all QuickDrop boxes.
    |
    */
    'max_file_size'      => env('QUICKDROP_MAX_FILE_SIZE', 1073741824), // 1GB
    'max_files'          => env('QUICKDROP_MAX_FILES', 100),
    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-rar-compressed',
        'image/jpeg',
        'image/png',
        'text/plain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiration Options
    |--------------------------------------------------------------------------
    |
    | Available expiration times for QuickDrop boxes.
    | Values are in minutes.
    |
    */
    'expiration_options' => [
        ['value' => 60, 'label' => '1 Hour'],
        ['value' => 360, 'label' => '6 Hours'],
        ['value' => 720, 'label' => '12 Hours'],
        ['value' => 1440, 'label' => '24 Hours'],
        ['value' => 2880, 'label' => '2 Days'],
        ['value' => 7200, 'label' => '5 Days'],
        ['value' => 10080, 'label' => '7 Days'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference Number Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the reference number field (e.g., PO number, invoice number)
    |
    */
    'reference_number' => [
        'enabled'    => env('QUICKDROP_REFERENCE_ENABLED', false),
        'label'      => env('QUICKDROP_REFERENCE_LABEL', 'Reference Number'),
        'help_text'  => env('QUICKDROP_REFERENCE_HELP', 'Enter a reference number (e.g., PO number, invoice number)'),
        'required'   => env('QUICKDROP_REFERENCE_REQUIRED', true),
        'validation' => [
            'pattern'       => env('QUICKDROP_REFERENCE_PATTERN', '^[A-Za-z0-9-]{3,50}$'),
            'min_length'    => env('QUICKDROP_REFERENCE_MIN_LENGTH', 3),
            'max_length'    => env('QUICKDROP_REFERENCE_MAX_LENGTH', 50),
            'error_message' => env('QUICKDROP_REFERENCE_ERROR_MESSAGE', 'Reference number must be 3-50 characters long and can only contain letters, numbers, and hyphens.'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for new QuickDrop boxes.
    |
    */
    'defaults' => [
        'expires_in_minutes' => 1440, // 24 hours
    ],
];
