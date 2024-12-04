<?php

$original = [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),
    'jwt_secret' => env('JWT_SECRET'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];


$develop = [
    'BASE_URL_DASHBOARD_BBL' => env('BASE_URL_DASHBOARD_BBL', 'https://dashboard.epayco.io'),
    'BASE_URL_BBL' => 'https://shops.epayco.xyz',
    'BASE_URL_EPAYCO' => 'https://epayco.xyz',
    'URL_KHEPRI' => 'https://khepri-bbl.epayco.io',
    "BASE_URL_SUBSCRIPTION" => "https://dashboard.epayco.xyz/vende/suscripcion",
    'BASE_URL_ANUKIS'=>'https://anukis-bbl.epayco.io',
    'BASE_URL_ELOGISTICA'=>'https://logistica-v1.epayco.io',
    'BASE_URL_SHOPS' => 'https://shops.epayco.io',
    'BASE_URL_SUSCRIPCIONES'=>'https://api.secure.epayco.io',
    'BASE_URL_APIFY'=>'https://apify.epayco.io',
    'MS_NOTIFICATIONS_BBL_URL'=>'https://notifications.epayco.io',
    'AWS_BASE_PUBLIC_URL_SHOPS' => 'https://multimedia-bbl-epayco.s3.amazonaws.com'

];

$staging = [
    'BASE_URL_DASHBOARD_BBL' => env('BASE_URL_DASHBOARD_BBL', 'https://dashboard.epayco.io'),
    'BASE_URL_BBL' => 'https://shops.epayco.io',
    'BASE_URL_EPAYCO' => 'https://epayco.io',
    'URL_KHEPRI' => 'https://khepri-bbl.epayco.io',
    "BASE_URL_SUBSCRIPTION" => "https://dashboard.epayco.io/vende/suscripcion",
    'BASE_URL_SHOPS' => 'https://shops.epayco.io',
    'BASE_URL_ANUKIS'=>'https://anukis-bbl.epayco.io',
    'BASE_URL_ELOGISTICA'=>'https://logistica-v1.epayco.io',
    'BASE_URL_SUSCRIPCIONES'=>'https://api.secure.epayco.io',
    'BASE_URL_APIFY'=>'https://apify.epayco.io',
    'MS_NOTIFICATIONS_BBL_URL'=>'https://notifications.epayco.io',
    'AWS_BASE_PUBLIC_URL_SHOPS' => 'https://multimedia-bbl-epayco.s3.amazonaws.com'

];

$green = [
    'BASE_URL_DASHBOARD_BBL' => env('BASE_URL_DASHBOARD_BBL', 'https://dashboar-green.epayco.com'),
    'BASE_URL_BBL' => 'https://shops.epayco.co',
    'BASE_URL_SHOPS' => 'https://shops.epayco.co',
    'BASE_URL_EPAYCO' => 'https://epayco.me',
    'URL_KHEPRI' => 'https://khepri-bbl.epayco.com',
    "BASE_URL_SUBSCRIPTION" => "https://dashboard-green.epayco.com/vende/suscripcion",
    'BASE_URL_ANUKIS'=>'https://anukis-bbl.epayco.com',
    'BASE_URL_ELOGISTICA'=>'https://logistica-v1.epayco.com',
    'BASE_URL_SUSCRIPCIONES'=>'https://api.secure.payco.co',
    'BASE_URL_APIFY'=>'https://apify.epayco.co',
    'MS_NOTIFICATIONS_BBL_URL'=>'https://notifications.epayco.com',
    'AWS_BASE_PUBLIC_URL_SHOPS' => 'https://multimedia-bbl-epayco-prod.s3.amazonaws.com'

];

$master = [
    'BASE_URL_DASHBOARD_BBL' => env('BASE_URL_DASHBOARD_BBL', 'https://dashboard.epayco.com'),
    'BASE_URL_SHOPS' => 'https://shops.epayco.co',
    'BASE_URL_BBL' => 'https://shops.epayco.co',
    'BASE_URL_EPAYCO' => 'https://epayco.me',
    'URL_KHEPRI' => 'https://khepri-bbl.epayco.co',
    "BASE_URL_SUBSCRIPTION" => "https://dashboard.epayco.com/vende/suscripcion",
    'BASE_URL_ANUKIS'=>'https://anukis-bbl.epayco.co',
    'BASE_URL_ELOGISTICA'=>'https://logistica-v1.epayco.com',
    'BASE_URL_SUSCRIPCIONES'=>'https://api.secure.payco.co',
    'BASE_URL_APIFY'=>'https://apify.epayco.co',
    'MS_NOTIFICATIONS_BBL_URL'=>'https://notifications.epayco.co',
    'AWS_BASE_PUBLIC_URL_SHOPS' => 'https://multimedia-bbl-epayco-prod.s3.amazonaws.com'

];

$local = [
    'BASE_URL_DASHBOARD_BBL' => env('BASE_URL_DASHBOARD_BBL', 'http://localhost:8010'),
    'BASE_URL_BBL' => 'https://shops.epayco.io',
    'BASE_URL_SHOPS' => 'https://shops.epayco.io',
    'BASE_URL_EPAYCO' => 'https://epayco.io',
    'URL_KHEPRI' => 'https://khepri-bbl.epayco.io',
    'BASE_URL_ANUKIS'=>'https://anukis-bbl.epayco.io',
    'BASE_URL_ELOGISTICA'=>'https://logistica-v1.epayco.io',
    'BASE_URL_SUSCRIPCIONES'=>'https://api.secure.epayco.io',
    'BASE_URL_APIFY'=>'https://apify.epayco.io',
    'MS_NOTIFICATIONS_BBL_URL'=>'https://notifications.epayco.io',
    'AWS_BASE_PUBLIC_URL_SHOPS' => 'https://multimedia-bbl-epayco.s3.amazonaws.com'

];


return array_merge($original, ${env('AMBIENTE', 'develop')});
