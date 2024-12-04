<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The default SMS Driver
    |--------------------------------------------------------------------------
    |
    | The default sms driver to use as a fallback when no driver is specified
    | while using the SMS component.
    |
    */
    'default' => env('SMS_DRIVER', 'hablameco'),

    /*
    |--------------------------------------------------------------------------
    | HablameCo Driver Configuration
    |--------------------------------------------------------------------------
    */
    'hablameco' => [
        'account' => env('SMS_HABLAMECO_ACCOUNT', ''),
        'apiKey' => env('SMS_HABLAMECO_APIKEY', ''),
        'token' => env('SMS_HABLAMECO_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Set New Provider Driver Configuration
    |--------------------------------------------------------------------------
    */
    'newprovider' => []
];
