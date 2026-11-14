<?php

return [
    'defaults' => [
        'app_name' => env('APP_BRAND_NAME', 'AR POS'),
        'app_tagline' => env('APP_BRAND_TAGLINE', 'Aksesories & Voucher'),
        'app_description' => 'Sistem POS dan manajemen penjualan toko retail, aksesoris, dan voucher.',
        'company_address' => '',
        'company_whatsapp' => '',
        'company_logo' => '',
        'hiwa_enabled' => false,
        'hiwa_token_device' => env('HIWA_TOKEN_DEVICE', ''),
        'fonnte_enabled' => false,
        'fonnte_token' => env('FONNTE_TOKEN', ''),
    ],

    'encrypted_keys' => [
        'hiwa_token_device',
        'fonnte_token',
    ],

    'json_array_keys' => [
    ],
];
