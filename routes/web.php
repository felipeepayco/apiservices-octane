<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */





$router->get('/ping', function () {
    return sprintf('%s@%s', config('app.name'), config('app.version'));
});

$router->get('/ip', function () {
    return array(
        'ip' => getHostByName(getHostName()),
        'hostName' => getHostName(),
    );
});

//Login para obtener bearer token
$router->post('/login', ['uses' => 'AuthController@authenticate']);
$router->post('/login/mail', ['uses' => 'AuthController@authenticate_email']);
$router->post('/login/epayco', ['uses' => 'AuthController@autenticacion_epayco']);
$router->get('/loginAutomaticByEpayco/{jwt}', ['uses' => 'AuthController@loginAutomaticByEpayco']);

$router->post('/departments', 'MasterController@getDepartments');
$router->post('/cities', 'MasterController@getCities');
$router->post('/countries', 'MasterController@getCountries');

//Agrupamos la rutas para que pasen por el midleware de jwt
$router->group(
    ['middleware' => 'jwt.auth','namespace'=>app('api.version')],
    function () use ($router) {

        $router->get('/configuration/keys', 'ConfigurationController@getKeys');
        $router->post('/client/keys', 'ClientController@listKeysClient');
        $router->post(
            '/client/edit',
            'ApiProfileController@editProfile'
        );
        /*

        //// MAESTROS ///////////////////////////////////////////////////////
        

        /** v2 para elastic */

   

        $router->post('/catalogue/products/update', 'ApiCatalogueProductController@catalogueProductUpdateElastic');
        $router->post('/catalogue/products/delete', 'ApiCatalogueProductController@catalogueProductElasticDelete');

        $router->post('/v2/catalogue/products', 'ApiCatalogueProductController@listproductsElastic');
        $router->post('/v2/catalogue/productsKhepri', 'ApiCatalogueProductController@listproductsElastic');

        $router->post('/v2/catalogue/products/create', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueProductController@catalogueProductNewElastic',
        ]);

        $router->post('/v2/catalogue/products/delete', 'ApiCatalogueProductController@catalogueProductElasticDelete');
        $router->post('/v2/catalogue/products/update', 'ApiCatalogueProductController@catalogueProductNewElastic');
        $router->post('/v2/catalogue/topsellingproducts', 'ApiCatalogueProductController@topSellingProductsElastic');

        //BUYERS
        $router->post('/buyers', 'BuyerController@getBuyers');

        $router->post('/buyers/create', [
            'middleware' => 'activeSubscription',
            'uses' => 'BuyerController@create',
        ]);
        $router->post('/buyers/delete', 'BuyerController@delete');
        $router->post('/buyers/update', [
            'middleware' => 'activeSubscription',
            'uses' => 'BuyerController@update',
        ]);

         //NOYES
         $router->post('/buyers-notes', 'BuyerNoteController@getBuyers');
         $router->post('/buyers-notes/list', 'BuyerNoteController@index');
         $router->post('/buyers-notes/create', 'BuyerNoteController@create');
         $router->post('/buyers-notes/delete', 'BuyerNoteController@delete');
        //  $router->post('/buyers-notes/update', 'BuyerNoteController@update');
 

        ////////////////////////////
        $router->post('/catalogue/products', 'ApiCatalogueProductController@listproductsElastic');
        $router->get('/catalogue/products', 'ApiCatalogueProductController@listproductsElastic');

        $router->post('/catalogue/shoppingcart', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueShoppingCartController@createShoppingCart',
        ]);
        $router->post('/catalogue/shoppingcart/list', 'ApiCatalogueShoppingCartController@listShoppingCart');
        $router->get('/catalogue/shoppingcart/find', 'ApiCatalogueShoppingCartController@getShoppingCart');
        $router->post('/catalogue/shoppingcart/find', 'ApiCatalogueShoppingCartController@getShoppingCart');
        $router->post('/catalogue/shoppingcart/emptycart', 'ApiCatalogueShoppingCartController@checkEmptyCart');
        $router->post('/catalogue/shoppingcart/empty', 'ApiCatalogueShoppingCartController@emptyShoppingCart');
        $router->post('/catalogue/shoppingcart/shipping', 'ApiCatalogueShoppingCartController@setShippingInfo');
        $router->post('/catalogue/shoppingcart/checkShoppingcart', 'ApiCatalogueShoppingCartController@checkShoppingCart');
        $router->post('/catalogue/shoppingcart/removeItem', 'ApiCatalogueShoppingCartController@removeItem');
        $router->post('/catalogue/shoppingcart/updateCarts', 'ApiCatalogueShoppingCartController@updateCarts');

        $router->post('/v2/search/buyer', 'ApiCatalogueShoppingCartController@getShippingInfo');

        //NO ELIMINAR ESTE DE ABAJO
        $router->post('/catalogue/shoppingcart/checkout/confirmation', 'ApiCatalogueShoppingCartController@checkoutConfirmation');
        $router->post('/catalogue/products/create', 'ApiCatalogueProductController@catalogueProductNewElastic');
        $router->post('/catalogue/products/update', 'ApiCatalogueProductController@catalogueProductUpdateElastic');
        $router->post('/catalogue/products/delete', 'ApiCatalogueProductController@catalogueProductElasticDelete');
        $router->get('/catalogue/products/delete', 'ApiCatalogueProductController@catalogueProductElasticDelete');
        $router->post('/catalogue/products/activeInactive', 'ApiCatalogueProductController@catalogueProductElasticActiveInactive');

        $router->post('/catalogue', 'ApiCatalogueControllerv2@listcatalogue');
        $router->get('/catalogue', 'ApiCatalogueControllerv2@listcatalogue');

        $router->post('/catalogue/create', 'ApiCatalogueControllerv2@catalogueNew');
        $router->post('/catalogue/update', 'ApiCatalogueControllerv2@catalogueNew');
        $router->post('/catalogue/get-receipt', 'ApiCatalogueControllerv2@getCatalogueReceipt');

        $router->post('/catalogue/delete', 'ApiCatalogueControllerv2@catalogueDelete');
        $router->delete('/v2/catalogue/delete', 'ApiCatalogueControllerv2@catalogueDelete');
        $router->get('/v2/catalogue/delete', 'ApiCatalogueControllerv2@catalogueDelete');
        $router->post('/v2/catalogue/delete', 'ApiCatalogueControllerv2@catalogueDelete');

        $router->post('/catalogue/inactive', 'ApiCatalogueControllerv2@catalogueInactive');
        $router->post('/v2/catalogue/inactive', 'ApiCatalogueControllerv2@catalogueInactive');

        //DISCOUNT CODES

        $router->post('/catalogue/discount-codes', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueControllerv2@catalogueDiscountCode',
        ]);
        $router->post('/catalogue/activate-inactivate-discount-codes', 'ApiCatalogueControllerv2@catalogueActivateInactivateDiscountCode');
        $router->post('/catalogue/apply-discount-codes', 'ApiCatalogueControllerv2@catalogueApplyDiscountCode');

        $router->post('/catalogue/discount-codes/list', 'ApiCatalogueControllerv2@catalogueDiscountCodeList');
        $router->post('/catalogue/delete-discount-codes', 'ApiCatalogueControllerv2@catalogueDiscountCodeDelete');
        //endpoints v2 para integrar el catalogo con elasticsearch
        $router->post('/v2/catalogue', 'ApiCatalogueControllerv2@listcatalogue');
        $router->get('/v2/catalogue', 'ApiCatalogueControllerv2@listCatalogue');

        $router->post('/v2/catalogue/create', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueControllerv2@catalogueNew',
        ]);

        $router->post('/v2/catalogue/changeStatus', 'ApiCatalogueControllerv2@catalogueChangeStatus');

        
        //CUSTOMERS

        $router->post('/customers', 'ApiCustomerController@customerNew');
        $router->post('/customers/edit-subdomain', 'ApiCustomerController@editSubDomain');

        //SUBSCRIPTIONS

        $router->post('/subscriptions', 'ApiSubscriptionController@subscriptionNew');

        $router->post('/subscriptions/deshabilitar-notificacion', 'ApiSubscriptionController@disableSubscription');
        $router->post('/subscriptions/cancel', 'ApiSubscriptionController@subscriptionCancel');
        $router->post('/subscriptions/renew', 'ApiSubscriptionController@subscriptionRenew');

        $router->post('/subscriptions/confirm', 'ApiSubscriptionController@confirmChangeSubscription');
        //--------------CENTRO DE ENDPOINTS------------------------------------
    

        $router->get(
            '/v2/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );
        $router->post(
            '/v2/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );

        //fin endpoints v2 para integrar el catalogo con elasticsearch

        $router->get(
            '/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );
        $router->post(
            '/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );

        $router->get(
            '/catalogue/category/create',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew'
        );
        $router->post(
            '/catalogue/category/create',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew'
        );

        $router->get(
            '/catalogue/category/update',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesUpdate'
        );

        $router->post(
            '/catalogue/category/update',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesUpdate'
        );

        $router->get(
            '/subdomain/create',
            'ApiConfigurationGeneralController@createOrUpdateSubdomain'
        );
        $router->post(
            '/subdomain/create',
            'ApiConfigurationGeneralController@createOrUpdateSubdomain'
        );

        $router->post('/vende/plan/restrictions/update', 'ApiVendeController@planRestrictionUpdate');
        $router->get(
            '/v2/catalogue/category/create',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew'
        );

        $router->post('/v2/catalogue/category/create', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew',
        ]);


        $router->post('/v2/catalogue/category/update', [
            'middleware' => 'activeSubscription',
            'uses' => 'ApiCatalogueCategoriesControllerv2@catalogueCategoriesUpdate',
        ]);

        //fin endpoints v2 para integrar el catalogo con elasticsearch

        $router->get(
            '/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );
        $router->post(
            '/catalogue/category',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesList'
        );

        $router->get(
            '/catalogue/category/create',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew'
        );
        $router->post(
            '/catalogue/category/create',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesNew'
        );

        $router->get(
            '/catalogue/category/update',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesUpdate'
        );

        $router->post(
            '/catalogue/category/update',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesUpdate'
        );

        ////// Mi ePayco
        $router->post('/miepayco/showconfiguration', 'ApiMiePaycoController@showConfiguration');
        //Plan Babilonia
        $router->post('/tokenCardDelete', 'ApiTokenCardController@tokenCardDelete');
        $router->post('/tokenCardRegister', 'ApiTokenCardController@tokenCardRegister');
        $router->post('/tokenCardDefault', 'ApiTokenCardController@tokenCardDefault');
        ///////Subscriptions
        $router->post('/subscription/token/card/delete', 'ApiTokenCardController@deleteTokenCard');
        $router->post('/subscription/charge', 'ApiSubscriptionController@charge');
        $router->post('/subscription/change/plan', 'ApiSubscriptionController@changePlan');
        $router->post('/subscription', 'ApiSubscriptionController@subscription');
        $router->post('/subscription/list/invoices', 'ApiSubscriptionController@listInvoices');

        $router->post('/vende/plan/product', 'ApiVendeController@getPlanByProduct');

        $router->post('/vende/plan/restrictions/update', 'ApiVendeController@planRestrictionUpdate');

        //SHOPPINGCARTS
        $router->post('/vende/loadDataConfigDelivery', 'ApiVendeShoppingcartController@loadDataConfigDelivery');

        $router->get(
            '/v2/catalogue/category/delete',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesDelete'
        );

        $router->post(
            '/v2/catalogue/category/delete',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesDelete'
        );

        $router->get(
            '/catalogue/category/delete',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesDelete'
        );
        $router->post(
            '/catalogue/category/delete',
            'ApiCatalogueCategoriesControllerv2@catalogueCategoriesDelete'
        );

        $router->get(
            '/catalogue/category/edit',
            'ApiCatalogueCategoriesController@catalogueCategoriesEdit'
        );
        $router->post(
            '/catalogue/category/edit',
            'ApiCatalogueCategoriesController@catalogueCategoriesEdit'
        );

        $router->post('/subscriptions/customer', 'ApiTokenCustomerController@getCustomer');
        $router->get('/subscriptions/customer', 'ApiTokenCustomerController@getCustomer');

        $router->get('/subscriptions/customers', 'ApiTokenCustomerController@getCustomers');

        $router->post('/subscriptions/customer/add/new/token', 'ApiTokenCustomerController@addNewTokenToCustomer');
        $router->post('/subscriptions/customer/update', 'ApiTokenCustomerController@updateCustomer');
        $router->post('/subscriptions/customer/add/new/token/default', 'ApiTokenCustomerController@addDefaultCard');

        ///////////// LINKS
        $router->get('/links/{code_id}', 'LinksController@show');
        $router->get('/links/stats/{code_id}', 'LinkStatsController@show');

        $router->post('/links', 'LinksController@store');

        ////// Procesar Transacciones por pse y tc
        $router->post(
            '/token/card',
            'ApiTokenCardController@getTokenCard'
        );

        $router->post(
            '/token/customer',
            'ApiTokenCustomerController@getTokenCustomer'
        );
        $router->post('/elogistica/providers', 'ApiProviderDeliveryController@listProviders');
        $router->post('/elogistica/departaments', 'ApiProviderDeliveryController@listDepartaments');
        $router->post('/elogistica/cities', 'ApiProviderDeliveryController@listCities');
        $router->post('/elogistica/quote', 'ApiProviderDeliveryController@quote');
        $router->post('/elogistica/guide', 'ApiProviderDeliveryController@guide');
        $router->post('/elogistica/pickup', 'ApiProviderDeliveryController@pickup');
        $router->post('/elogistica/create/configuration', 'ApiProviderDeliveryController@createConfiguration');
        $router->post('/elogistica/update/configuration', 'ApiProviderDeliveryController@updateConfiguration');
    }

);
