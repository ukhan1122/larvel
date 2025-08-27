<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI')
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TEMP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],
    'postex' => [
        'token' => env('POSTEX_TOKEN'),
    ],
    'sendpk' => [
        'api_key' => env('SENDPK_API')
    ],
    'imagekit' => [
        'public_key'    => env('IMAGEKIT_PUBLIC_KEY'),
        'private_key'   => env('IMAGEKIT_PRIVATE_KEY'),
        'url_endpoint'  => env('IMAGEKIT_URL_ENDPOINT'),
    ],

    'blueex' => [
        'username'              => env('BLUEEX_USERNAME', 'closyyyy'),
        'password'              => env('BLUEEX_PASSWORD', '12345'),
        'endpoint'              => env('BLUEEX_ENDPOINT', 'https://apis.blue-ex.com/api/V4/CreateBooking'),
        'service_code'          => env('BLUEEX_SERVICE_CODE', 'BG'),
        'payment_type'          => env('BLUEEX_PAYMENT_TYPE', 'COD'),
        'fragile'               => env('BLUEEX_FRAGILE', 'N'),
        'parcel_type'           => env('BLUEEX_PARCEL_TYPE', 'P'),
        'insurance_require'     => env('BLUEEX_INSURANCE_REQUIRE', 'N'),
        'insurance_value'       => env('BLUEEX_INSURANCE_VALUE', '0'),
        'testbit'               => env('BLUEEX_TESTBIT', 'Y'),
        'cn_generate'           => env('BLUEEX_CN_GENERATE', 'Y'),
        'multi_pickup'          => env('BLUEEX_MULTI_PICKUP', 'Y'),
        'default_item_weight'   => env('BLUEEX_DEFAULT_ITEM_WEIGHT', 0.5),
        'default_total_weight'  => env('BLUEEX_DEFAULT_TOTAL_WEIGHT', 1.0),

        // shipper fallbacks if seller profile lacks address
        'default_shipper_name'    => env('BLUEEX_DEFAULT_SHIPPER_NAME', 'Closyyyy Seller'),
        'default_shipper_email'   => env('BLUEEX_DEFAULT_SHIPPER_EMAIL', 'noreply@closyyyy.test'),
        'default_shipper_phone'   => env('BLUEEX_DEFAULT_SHIPPER_PHONE', '03000000000'),
        'default_shipper_address_line_1' => env('BLUEEX_DEFAULT_SHIPPER_ADDR1', 'Pickup Location'),
        'default_shipper_city'    => env('BLUEEX_DEFAULT_SHIPPER_CITY', 'Karachi'),
    ],
];
