<?php

namespace App\Providers;

use App\Events\Subscriptions\Process\ActiveDomiciliationsEvent;
use App\Listeners\Subscriptions\Process\ActiveDomiciliationsListener;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ValidacionesGeneralesApiConsultaMovimientoEvent' => [
            'App\Listeners\ValidacionesGeneralesApiConsultaMovimientoListener',
        ],
        'App\Events\ApiConsultaMovimientoEvent' => [
            'App\Listeners\ApiConsultaMovimientoListener',
        ],
        'App\Events\GetKeysEvent' => [
            'App\Listeners\GetKeysListener',
        ],
        'App\Events\ValidationGeneralSellUpdateEvent' => [
            'App\Listeners\ValidationGeneralSellUpdateListener',
        ],
        'App\Events\ConsultSellUpdateEvent' => [
            'App\Listeners\ConsultSellUpdateListener',
        ],
        ////////////////// elastic ////////////////////////
        'App\Events\CatalogueProductNewElasticEvent' => [
            'App\Listeners\Catalogue\Process\Product\CatalogueProductNewElasticListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductNewElasticEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueProductNewElasticListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductListElasticEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueProductListElasticListener',
        ],

        'App\Events\ValidationGeneralCatalogueProductDeleteElasticEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueProductDeleteElasticListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductActiveInactiveElasticEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueProductActiveInactiveElasticListener',
        ],

        'App\Events\CatalogueProductDeleteElasticEvent' => [
            'App\Listeners\Catalogue\Process\Product\CatalogueProductDeleteElasticListener',
        ],

        'App\Events\ConsultCatalogueProductListElasticEvent' => [
            'App\Listeners\Catalogue\Process\Product\ConsultCatalogueProductListElasticListener',
        ],
        'App\Events\ConsultCatalogueTopSellingProductsListElasticEvent' => [
            'App\Listeners\Catalogue\Process\Product\ConsultCatalogueTopSellingProductsListElasticListener',
        ],

        'App\Events\ValidationGeneralCatalogueProductActiveInactiveElasticEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueProductActiveInactiveElasticListener',
        ],
        'App\Events\CatalogueProductActiveInactiveElasticEvent' => [
            'App\Listeners\Catalogue\Process\Product\CatalogueProductActiveInactiveElasticListener',
        ],
        /////////////////////////////////////////////////////////////
        'App\Events\ConsultSellDeleteEvent' => [
            'App\Listeners\ConsultSellDeleteListener',
        ],

        'App\Events\ValidationGeneralSellDeleteEvent' => [
            'App\Listeners\ValidationGeneralSellDeleteListener',
        ],

        'App\Events\ValidationGeneralCatalogueProductListEvent' => [
            'App\Listeners\ValidationGeneralCatalogueProductListListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductNewEvent' => [
            'App\Listeners\ValidationGeneralCatalogueProductNewListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductUpdateEvent' => [
            'App\Listeners\ValidationGeneralCatalogueProductUpdateListener',
        ],
        'App\Events\ConsultCatalogueProductListEvent' => [
            'App\Listeners\ConsultCatalogueProductListListener',
        ],
        'App\Events\ConsultCatalogueTopSellingProductsListEvent' => [
            'App\Listeners\ConsultCatalogueTopSellingProductsListListener',
        ],
        'App\Events\ShoppingCart\Process\ProcessPaymentReceiptEvent' => [
            'App\Listeners\ShoppingCart\Process\ProcessPaymentReceiptListener',
        ],

        'App\Events\ShoppingCart\Validation\ValidationPaymentReceiptEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationPaymentReceiptListener',
        ],
        'App\Events\ShoppingCart\Process\ProcessCreateShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Process\ProcessCreateShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Process\ProcessGetShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Process\ProcessGetShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Process\CheckShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Process\CheckShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationCheckEmptyCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationCheckEmptyCartListener',
        ],
        'App\Events\ShoppingCart\Process\CheckEmptyCartEvent' => [
            'App\Listeners\ShoppingCart\Process\CheckEmptyCartListener',
        ],
        'App\Events\ShoppingCart\Process\EmptyCartEvent' => [
            'App\Listeners\ShoppingCart\Process\EmptyCartListener',
        ],

        'App\Events\ShoppingCart\Validation\ValidationListShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationListShoppingCartListener',
        ],

        'App\Events\ShoppingCart\Process\ProcessListShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Process\ProcessListShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationEmptyCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationEmptyCartListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationCreateShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationCreateShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationGetShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationGetShoppingCartListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationSetShippingInfoEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationSetShippingInfoListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationGetShippingInfoEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationGetShippingInfoListener',
        ],
        'App\Events\ShoppingCart\Process\SetShippingInfoEvent' => [
            'App\Listeners\ShoppingCart\Process\SetShippingInfoListener',
        ],
        'App\Events\ShoppingCart\Process\GetShippingInfoEvent' => [
            'App\Listeners\ShoppingCart\Process\GetShippingInfoListener',
        ],
        'App\Events\ShoppingCart\Process\ProcessCheckoutConfirmationEvent' => [
            'App\Listeners\ShoppingCart\Process\ProcessCheckoutConfirmationListener',
        ],
        'App\Events\ShoppingCart\Validation\ValidationCheckoutShoppingCartEvent' => [
            'App\Listeners\ShoppingCart\Validation\ValidationCheckoutShoppingCartListener',
        ],
        'App\Events\CatalogueProductNewEvent' => [
            'App\Listeners\CatalogueProductNewListener',
        ],
        'App\Events\ValidationGeneralCatalogueListEvent' => [
            'App\Listeners\ValidationGeneralCatalogueListListener',
        ],
        'App\Events\ConsultCatalogueListEvent' => [
            'App\Listeners\ConsultCatalogueListListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueNewEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueNewListener',
        ],
        'App\Events\Catalogue\Process\CatalogueNewEvent' => [
            'App\Listeners\Catalogue\Process\CatalogueNewListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueUpdateEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueUpdateListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueDeleteEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueDeleteListener',
        ],
        'App\Events\Catalogue\Process\CatalogueDeleteEvent' => [
            'App\Listeners\Catalogue\Process\CatalogueDeleteListener',
        ],

        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueInactiveEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueInactiveListener',
        ],
        'App\Events\Catalogue\Process\CatalogueInactiveEvent' => [
            'App\Listeners\Catalogue\Process\CatalogueInactiveListener',
        ],
        // Agregar Eventos dentro de la carpeta \Events\Catalogue y manejar los endpoints
        // v2 para catalogos con elasticsearch
        'App\Events\Catalogue\CatalogueProductNewEvent' => [
            'App\Listeners\Catalogue\CatalogueProductNewListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueListEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueListListener',
        ],
        'App\Events\Catalogue\Process\ConsultCatalogueListEvent' => [
            'App\Listeners\Catalogue\Process\ConsultCatalogueListListener',
        ],
        'App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListener',
        ],
        'App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Validation\DiscountCode\ValidationCatalogueDiscountCodeListener',
        ],

        'App\Events\Catalogue\Process\DiscountCode\CatalogueDeleteDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Process\DiscountCode\CatalogueDeleteDiscountCodeListener',
        ],
        'App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDeleteDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Validation\DiscountCode\ValidationCatalogueDeleteDiscountCodeListener',
        ],

        'App\Events\Catalogue\Process\DiscountCode\CatalogueActivateInactivateDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Process\DiscountCode\CatalogueActivateInactivateDiscountCodeListener',
        ],
        'App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueActivateInactivateDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Validation\DiscountCode\ValidationCatalogueActivateInactivateDiscountCodeListener',
        ],
        'App\Events\Catalogue\Process\DiscountCode\CatalogueApplyDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Process\DiscountCode\CatalogueApplyDiscountCodeListener',
        ],
        'App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueApplyDiscountCodeEvent' => [
            'App\Listeners\Catalogue\Validation\DiscountCode\ValidationCatalogueApplyDiscountCodeListener',
        ],
        'App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListEvent' => [
            'App\Listeners\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListListener',
        ],
        // Fin agregar Eventos dentro de la carpeta \Events\Catalogue y manejar los endpoints
        // v2 para catalogos con elasticsearch

        'App\Events\ValidationGeneralCatalogueCategoriesListEvent' => [
            'App\Listeners\ValidationGeneralCatalogueCategoriesListListener',
        ],
        'App\Events\ConsultCatalogueCategoriesListEvent' => [
            'App\Listeners\ConsultCatalogueCategoriesListListener',
        ],
        'App\Events\ValidationGeneralCatalogueCategoriesNewEvent' => [
            'App\Listeners\ValidationGeneralCatalogueCategoriesNewListener',
        ],
        'App\Events\ConsultCatalogueCategoriesNewEvent' => [
            'App\Listeners\ConsultCatalogueCategoriesNewListener',
        ],
        'App\Events\ValidationGeneralCatalogueCategoriesDeleteEvent' => [
            'App\Listeners\ValidationGeneralCatalogueCategoriesDeleteListener',
        ],
        'App\Events\ConsultCatalogueCategoriesDeleteEvent' => [
            'App\Listeners\ConsultCatalogueCategoryDeleteListener',
        ],
        'App\Events\ConsultCatalogueCategoriesEditEvent' => [
            'App\Listeners\ConsultCatalogueCategoriesEditListener',
        ],
        'App\Events\ValidationGeneralCatalogueCategoriesUpdateEvent' => [
            'App\Listeners\ValidationGeneralCatalogueCategoriesUpdateListener',
        ],
        'App\Events\ConsultCatalogueCategoriesUpdateEvent' => [
            'App\Listeners\ConsultCatalogueCategoriesUpdateListener',
        ],

        // Agregar Eventos dentro de la carpeta \Events\Catalogue y manejar los endpoints
        // v2 para categorias con elasticsearch
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesListEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueCategoriesListListener',
        ],
        'App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesListEvent' => [
            'App\Listeners\Catalogue\Process\Category\ConsultCatalogueCategoriesListListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesNewEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueCategoriesNewListener',
        ],
        'App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesNewEvent' => [
            'App\Listeners\Catalogue\Process\Category\ConsultCatalogueCategoriesNewListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesDeleteEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueCategoriesDeleteListener',
        ],
        'App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesDeleteEvent' => [
            'App\Listeners\Catalogue\Process\Category\ConsultCatalogueCategoryDeleteListener',
        ],
        'App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesEditEvent' => [
            'App\Listeners\Catalogue\Process\Category\ConsultCatalogueCategoriesEditListener',
        ],
        'App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesUpdateEvent' => [
            'App\Listeners\Catalogue\Validation\ValidationGeneralCatalogueCategoriesUpdateListener',
        ],
        'App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesUpdateEvent' => [
            'App\Listeners\Catalogue\Process\Category\ConsultCatalogueCategoriesUpdateListener',
        ],
        // Fin agregar Eventos dentro de la carpeta \Events\Catalogue y manejar los endpoints
        // v2 para categorias con elasticsearch


        'App\Events\ValidationGeneralCatalogueProductDeleteEvent' => [
            'App\Listeners\ValidationGeneralCatalogueProductDeleteListener',
        ],
        'App\Events\ValidationGeneralCatalogueProductActiveInactiveEvent' => [
            'App\Listeners\ValidationGeneralCatalogueProductActiveInactiveListener',
        ],
        'App\Events\CatalogueProductDeleteEvent' => [
            'App\Listeners\CatalogueProductDeleteListener',
        ],
        'App\Events\PayPalAssociateEvent' => [
            'App\Listeners\PayPalAssociateListener',
        ],
        'App\Events\ValidationGeneralPayPalAssociateEvent' => [
            'App\Listeners\ValidationGeneralPayPalAssociateListener',
        ],
        'App\Events\ValidationGeneralWithdrawPayPalEvent' => [
            'App\Listeners\ValidationWithdrawPayPalListener',
        ],
        'App\Events\PayPalWithdrawEvent' => [
            'App\Listeners\PayPalWithdrawListener',
        ],
        'App\Events\ePaycoWithdrawEvent' => [
            'App\Listeners\ePaycoWithdrawListener',
        ],
        'App\Events\ConsultProfileEditEvent' => [
            'App\Listeners\ConsultProfileEditListener',
        ],
        'App\Events\ValidationGeneralClientListKeysEvent' => [
            'App\Listeners\ValidationGeneralClientListKeysListener',
        ],
        'App\Events\ConsultClientListKeysEvent' => [
            'App\Listeners\ConsultClientListKeysListener',
        ],
        //CUSTOMERS
        'App\Events\Customer\Validation\ValidationCustomerNewEvent' => [
            'App\Listeners\Customer\Validation\ValidationCustomerNewListener',
        ],
        'App\Events\Customer\Process\CustomerNewEvent' => [
            'App\Listeners\Customer\Process\CustomerNewListener',
        ],

        'App\Events\Customer\Validation\ValidationCustomerEditSubDomainEvent' => [
            'App\Listeners\Customer\Validation\ValidationCustomerEditSubDomainListener',
        ],
        'App\Events\Customer\Process\CustomerEditSubDomainEvent' => [
            'App\Listeners\Customer\Process\CustomerEditSubDomainListener',
        ],
        'App\Events\Subscription\Validation\ValidationSubscriptionNewEvent' => [
            'App\Listeners\Subscription\Validation\ValidationSubscriptionNewListener',
        ],
        'App\Events\Subscription\Process\SubscriptionNewEvent' => [
            'App\Listeners\Subscription\Process\SubscriptionNewListener',
        ],
        'App\Events\Subscription\Process\SubscriptionCancelEvent' => [
            'App\Listeners\Subscription\Process\SubscriptionCancelListener',
        ],
        'App\Events\Subscription\Process\ProcessSubscriptionConfirmEvent' => [
            'App\Listeners\Subscription\Process\ProcessSubscriptionConfirmListener',
        ],
        'App\Events\Subscription\Validation\ValidationSubscriptionConfirmEvent' => [
            'App\Listeners\Subscription\Validation\ValidationSubscriptionConfirmListener',
        ],
        'App\Events\Subscription\Process\ProcessSubscriptionEvent' => [
            'App\Listeners\Subscription\Process\ProcessSubscriptionListener',
        ],
        'App\Events\Subscription\Validation\ValidationSubscriptionEvent' => [
            'App\Listeners\Subscription\Validation\ValidationSubscriptionListener',
        ],

        /////     Suscripciones   //////////

        //MongoTransaction
        'App\Events\MongoTransaction\Validation\ValidationDataMongoTransactionEvent' => [
            'App\Listeners\MongoTransaction\Validation\ValidationDataMongoTransactionListener',
        ],
        'App\Events\MongoTransaction\Process\ProcessCreateMongoTransactionEvent' => [
            'App\Listeners\MongoTransaction\Process\ProcessCreateMongoTransactionListener',
        ],
        //Transacción por franquicia

        ///Token
        'App\Events\Payments\Validation\ValidationTokenCardEvent' => [
            'App\Listeners\Payments\Validation\ValidationTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessTokenCardEvent' => [
            'App\Listeners\Payments\Process\ProcessTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessTokenCardV2Event' => [
            'App\Listeners\Payments\Process\ProcessTokenCardV2Listener',
        ],
        'App\Events\Payments\Process\ProcessChargeEvent' => [
            'App\Listeners\Payments\Process\ProcessChargeListener',
        ],
        'App\Events\Payments\Validation\ValidationChargeEvent' => [
            'App\Listeners\Payments\Validation\ValidationChargeListener',
        ],
        'App\Events\Payments\Validation\ValidationChangePlanEvent' => [
            'App\Listeners\Payments\Validation\ValidationChangePlanListener',
        ],
        'App\Events\Payments\Process\ProcessChangePlanEvent' => [
            'App\Listeners\Payments\Process\ProcessChangePlanListener',
        ],
        'App\Events\Subscription\Process\ProcessTokenCardDefaultEvent' => [
            'App\Listeners\Subscription\Process\ProcessTokenCardDefaultListener',
        ],
        'App\Events\Subscription\Validation\ValidationTokenCardDefaultEvent' => [
            'App\Listeners\Subscription\Validation\ValidationTokenCardDefaultListener',
        ],
        'App\Events\Payments\Validation\ValidationDebitTokenCardEvent' => [
            'App\Listeners\Payments\Validation\ValidationDebitTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessDebitTokenCardEvent' => [
            'App\Listeners\Payments\Process\ProcessDebitTokenCardListener',
        ],

        'App\Events\Payments\Validation\ValidationDeleteTokenCardEvent' => [
            'App\Listeners\Payments\Validation\ValidationDeleteTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessDeleteTokenCardEvent' => [
            'App\Listeners\Payments\Process\ProcessDeleteTokenCardListener',
        ],
        'App\Events\Payments\Validation\ValidationDeleteTokenCardV2Event' => [
            'App\Listeners\Payments\Validation\ValidationDeleteTokenCardV2Listener',
        ],
        'App\Events\Payments\Process\ProcessDeleteTokenCardV2Event' => [
            'App\Listeners\Payments\Process\ProcessDeleteTokenCardV2Listener',
        ],

        //Customer
        'App\Events\Payments\Validation\ValidationTokenCustomerEvent' => [
            'App\Listeners\Payments\Validation\ValidationTokenCustomerListener',
        ],
        'App\Events\Payments\Process\ProcessTokenCustomerEvent' => [
            'App\Listeners\Payments\Process\ProcessTokenCustomerListener',
        ],
        'App\Events\Payments\Validation\ValidationCustomerUpdateEvent' => [
            'App\Listeners\Payments\Validation\ValidationCustomerUpdateListener',
        ],
        'App\Events\Payments\Process\ProcessCustomerUpdateEvent' => [
            'App\Listeners\Payments\Process\ProcessCustomerUpdateListener',
        ],

        'App\Events\Payments\Validation\ValidationCustomerEvent' => [
            'App\Listeners\Payments\Validation\ValidationCustomerListener',
        ],
        'App\Events\Payments\Process\ProcessCustomerEvent' => [
            'App\Listeners\Payments\Process\ProcessCustomerListener',
        ],

        'App\Events\Payments\Validation\ValidationCustomersEvent' => [
            'App\Listeners\Payments\Validation\ValidationCustomersListener',
        ],
        'App\Events\Payments\Process\ProcessCustomersEvent' => [
            'App\Listeners\Payments\Process\ProcessCustomersListener',
        ],

        'App\Events\Payments\Validation\ValidationTokenCustomerNewTokenCardEvent' => [
            'App\Listeners\Payments\Validation\ValidationTokenCustomerNewTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessTokenCustomerNewTokenCardEvent' => [
            'App\Listeners\Payments\Process\ProcessTokenCustomerNewTokenCardListener',
        ],

        'App\Events\Payments\Validation\ValidationTokenCustomerDefaultTokenCardEvent' => [
            'App\Listeners\Payments\Validation\ValidationTokenCustomerDefaultTokenCardListener',
        ],
        'App\Events\Payments\Process\ProcessTokenCustomerDefaultTokenCardEvent' => [
            'App\Listeners\Payments\Process\ProcessTokenCustomerDefaultTokenCardListener',
        ],

        /////     Suscripciones   //////////

        ////      Cifin
        'App\Events\ClientValidation\ClientValidationCifinEvent' => [
            'App\Listeners\ClientValidation\ClientValidationCifinListener',
        ],
        'App\Events\ClientValidation\ClientValidationCifinResponseEvent' => [
            'App\Listeners\ClientValidation\ClientValidationCifinResponseListener',
        ],
        'App\Events\ClientValidation\ClientValidationQuestionsCifinEvent' => [
            'App\Listeners\ClientValidation\ClientValidationQuestionsCifinListener',
        ],
        'App\Events\ClientValidation\ClientValidationQuestionResponseCifinEvent' => [
            'App\Listeners\ClientValidation\ClientValidationQuestionResponseCifinListener',
        ],

        //        Listas restrictivas
        'App\Events\ValidationAccount\Validation\ValidationRestrictiveListEvent' => [
            'App\Listeners\ValidationAccount\Validation\ValidationRestrictiveListListener',
        ],
        'App\Events\ValidationAccount\Process\ProcessRestrictiveListEvent' => [
            'App\Listeners\ValidationAccount\Process\ProcessRestrictiveListListener',
        ],

        'App\Events\RestrictiveList\Process\ProcessRestrictiveListSaveLogEvent' => [
            'App\Listeners\RestrictiveList\Process\ProcessRestrictiveListSaveLogListener',
        ],



        //Predeterminar una cuenta
        'App\Events\AccountBank\Validation\ValidationAccountBankPredetermineEvent' => [
            'App\Listeners\AccountBank\Validation\ValidationAccountBankPredetermineListener',
        ],
        'App\Events\AccountBank\Process\ProcessAccountBankPredetermineEvent' => [
            'App\Listeners\AccountBank\Process\ProcessAccountBankPredetermineListener',
        ],

        ////recaudo////
        ///leer configuracion de proyecto archivo
        'App\Events\Billcollect\Validation\ValidationViewConfigProyectEvent' => [
            'App\Listeners\Billcollect\Validation\ValidationViewConfigProyectListener',
        ],
        'App\Events\Billcollect\Process\ProcessViewConfigProyectEvent' => [
            'App\Listeners\Billcollect\Process\ProcessViewConfigProyectListener',
        ],

        //crear factura
        'App\Events\Billcollect\Validation\ValidationCreateBillEvent' => [
            'App\Listeners\Billcollect\Validation\ValidationCreateBillListener',
        ],
        'App\Events\Billcollect\Process\ProcessCreateBillEvent' => [
            'App\Listeners\Billcollect\Process\ProcessCreateBillListener',
        ],

        //consultar facturas
        'App\Events\Billcollect\Validation\ValidationListBillEvent' => [
            'App\Listeners\Billcollect\Validation\ValidationListBillListener',
        ],
        'App\Events\Billcollect\Process\ProcessListBillEvent' => [
            'App\Listeners\Billcollect\Process\ProcessListBillListener',
        ],
        'App\Events\Billcollect\Validation\ValidationListBill2Event' => [
            'App\Listeners\Billcollect\Validation\ValidationListBill2Listener',
        ],
        'App\Events\Billcollect\Process\ProcessListBill2Event' => [
            'App\Listeners\Billcollect\Process\ProcessListBill2Listener',
        ],
        //editar facturas
        'App\Events\Billcollect\Validation\ValidationDetailBillEvent' => [
            'App\Listeners\Billcollect\Validation\ValidationDetailBillListener',
        ],
        'App\Events\Billcollect\Process\ProcessDetailBillEvent' => [
            'App\Listeners\Billcollect\Process\ProcessDetailBillListener',
        ],







        ///////////////////////////////// LINK DE PAGO PSE ///////////////////////////////////////////


        ///////////////////////////////// Colas ePayco ///////////////////////////////////////////
        'App\Events\QueuesEpayco\QueuesEpaycoEvent' => [
            'App\Listeners\QueuesEpayco\QueuesEpaycoListener',
        ],

        // Servicio de SMS
        'App\Events\Services\ServicesSmsEvent' => [
            'App\Listeners\Services\ServicesSmsListener',
        ],



        'App\Events\Payments\Validation\ValidationVtexTransactionPseEvent' => [
            'App\Listeners\Payments\Validation\ValidationVtexTransactionPseListener',
        ],
        'App\Events\Catalogue\Process\Plans\DowngradePlanEvent' => [
            'App\Listeners\Catalogue\Process\Plans\DowngradePlanListener',
        ],
        'App\Events\Catalogue\Validation\Plans\ValidationDowngradePlanEvent' => [
            'App\Listeners\Catalogue\Validation\Plans\ValidationDowngradePlanListener',
        ],

        //Client Networks
        'App\Events\ClientNetworks\Process\ProcessCreateOrUpdateClientNetworkEvent' => [
            'App\Listeners\ClientNetworks\Process\ProcessCreateOrUpdateClientNetworkListener',
        ],
        'App\Events\ClientNetworks\Validation\ValidationCreateOrUpdateClientNetworkEvent' => [
            'App\Listeners\ClientNetworks\Validation\ValidationCreateOrUpdateClientNetworkListener',
        ],
        'App\Events\ClientNetworks\Process\ProcessCreateOrUpdateClientNetworksByBatchEvent' => [
            'App\Listeners\ClientNetworks\Process\ProcessCreateOrUpdateClientNetworksByBatchListener',
        ],
        'App\Events\ClientNetworks\Validation\ValidationCreateOrUpdateClientNetworksByBatchEvent' => [
            'App\Listeners\ClientNetworks\Validation\ValidationCreateOrUpdateClientNetworksByBatchListener',
        ],
        'App\Events\ClientNetworks\Validation\ValidationUpdateClientPaymentMethodEvent' => [
            'App\Listeners\ClientNetworks\Validation\ValidationUpdateClientPaymentMethodListener',
        ],
        'App\Events\ClientNetworks\Process\ProcessUpdateClientPaymentMethodEvent' => [
            'App\Listeners\ClientNetworks\Process\ProcessUpdateClientPaymentMethodListener',
        ],
        'App\Events\ClientNetworks\Process\ProcessDeleteClientNetworkEvent' => [
            'App\Listeners\ClientNetworks\Process\ProcessDeleteClientNetworkListener',
        ],
        'App\Events\ClientNetworks\Validation\ValidationDeleteClientNetworkEvent' => [
            'App\Listeners\ClientNetworks\Validation\ValidationDeleteClientNetworkListener',
        ],



        'App\Events\Tickets\Validation\ValidationReopenTicketEvent' => [
            'App\Listeners\Tickets\Validation\ValidationReopenTicketListener',
        ],
        'App\Events\Tickets\Process\ProcessReopenTicketEvent' => [
            'App\Listeners\Tickets\Process\ProcessReopenTicketListener',
        ],
        'App\Events\Tickets\Validation\ValidationCreateTicketEvent' => [
            'App\Listeners\Tickets\Validation\ValidationCreateTicketListener',
        ],
        'App\Events\Tickets\Process\ProcessCreateTicketEvent' => [
            'App\Listeners\Tickets\Process\ProcessCreateTicketListener',
        ],
        'App\Events\Tickets\Validation\ValidationListTicketEvent' => [
            'App\Listeners\Tickets\Validation\ValidationListTicketListener',
        ],
        'App\Events\Tickets\Process\ProcessListTicketEvent' => [
            'App\Listeners\Tickets\Process\ProcessListTicketListener',
        ],
        'App\Events\Tickets\Validation\ValidationDetailTicketEvent' => [
            'App\Listeners\Tickets\Validation\ValidationDetailTicketListener',
        ],
        'App\Events\Tickets\Process\ProcessDetailTicketEvent' => [
            'App\Listeners\Tickets\Process\ProcessDetailTicketListener',
        ],
        'App\Events\Tickets\Validation\ValidationSaveTicketEvent' => [
            'App\Listeners\Tickets\Validation\ValidationSaveTicketListener',
        ],
        'App\Events\Tickets\Process\ProcessSaveTicketEvent' => [
            'App\Listeners\Tickets\Process\ProcessSaveTicketListener',
        ],
        //Reportes programados
        'App\Events\ReportesProgramados\Process\RpTipoListEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpTipoList',
        ],
        'App\Events\ReportesProgramados\Process\RpReferenciasListEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpReferenciasList',
        ],
        'App\Events\ReportesProgramados\Process\RpConfigListEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpConfigList',
        ],
        'App\Events\ReportesProgramados\Process\RpResultListEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpResultList',
        ],
        'App\Events\ReportesProgramados\Process\RpConfigByIdEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpConfigById',
        ],
        'App\Events\ReportesProgramados\Process\RpResultByIdEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpResultById',
        ],
        'App\Events\ReportesProgramados\Process\RpCreateConfigEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpCreateConfig',
        ],
        'App\Events\ReportesProgramados\Process\RpUpdateConfigEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpUpdateConfig',
        ],
        'App\Events\ReportesProgramados\Process\RpUpdateResultEvent' => [
            'App\Listeners\ReportesProgramados\Process\RpTipoListListener@RpUpdateResult',
        ],

        ///API SUBSCRITION V2
        'App\Events\ApiSubscriptionsV2\Customer\Process\AddPaymentMethodEvent' => [
            'App\Listeners\ApiSubscriptionsV2\Customer\Process\AddPaymentMethodListener',
        ],
        'App\Events\ApiSubscriptionsV2\Customer\Validation\ValidationAddPaymentMethodEvent' => [
            'App\Listeners\ApiSubscriptionsV2\Customer\Validation\ValidationAddPaymentMethodListener',
        ],
        'App\Events\ApiSubscriptionsV2\Customer\Process\GetCardsCustomerEvent' => [
            'App\Listeners\ApiSubscriptionsV2\Customer\Process\GetCardsCustomerListener',
        ],
        'App\Events\ApiSubscriptionsV2\Customer\Validation\ValidationGetCardsCustomerEvent' => [
            'App\Listeners\ApiSubscriptionsV2\Customer\Validation\ValidationGetCardsCustomerListener',
        ],
        // TERMINALES CREDIBANCOVNP

        'App\Events\CredibancoVNP\Process\ProcessTerminalsEvent' => [
            'App\Listeners\CredibancoVNP\Process\ProcessTerminalsListener',
        ],

        ActiveDomiciliationsEvent::class => [
            ActiveDomiciliationsListener::class,
        ],
    ];
}
