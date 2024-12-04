<?php


//Route::namespace('App\Http\Controllers\V2')->group(function () {
Route::namespace(app('api.version'))->group(function () {

    Route::post('/test', 'ApiVendeController@test');
});

Route::group([
    "middleware" => ["jwt.auth"],
], function () {

    Route::namespace(app('api.version'))->group(function () {

        //////// Vende
        Route::post('/vende/load', 'ApiVendeController@showConfigurationCatalogue');
        Route::post('/babilonia/configuraciones', 'ApiVendeController@configurationBabilonia');
        Route::post('/babilonia/query/cname', 'ApiVendeController@queryCname');
        Route::post('/babilonia/reboot/created_certificate', 'ApiVendeController@rebootCertificates');
    });
});
