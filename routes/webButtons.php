<?php

/*
|--------------------------------------------------------------------------
| Rutas Botones
|--------------------------------------------------------------------------
|
*/

$router->group(
    ['middleware' => 'jwt.auth'],
    function () use ($router) {

    }
);
