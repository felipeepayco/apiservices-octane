<?php

/*
|--------------------------------------------------------------------------
| Rutas PaycoLink
|--------------------------------------------------------------------------
|
*/
$router->group(
    ['middleware' => 'jwt.auth'],
    function () use ($router) {
        $router->post('/paycolink/payment/create','PaycolinkController@create');
    }
);
