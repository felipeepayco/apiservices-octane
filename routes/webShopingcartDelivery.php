<?php

/*
|--------------------------------------------------------------------------
| Rutas PaycoLink
|--------------------------------------------------------------------------
|
 */
$router->group(
    ['middleware' => 'jwt.auth', 'namespace' => app('api.version')],
    function () use ($router) {
        $router->post('/catalogue/shoppingcart/stateDelivery', 'ApiCatalogueShoppingCartController@changeStateDelivery');
        $router->post('/catalogue/shoppingcart/pickup', 'ApiCatalogueShoppingCartController@loadPickup');
    }
);
