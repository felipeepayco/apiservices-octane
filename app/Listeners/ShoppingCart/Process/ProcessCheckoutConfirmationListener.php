<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Models\BblClientesPasarelas;
use \Illuminate\Http\Request;
use App\Events\ShoppingCart\Process\ProcessCheckoutConfirmationEvent;
use App\Listeners\Services\LogisticaService;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Clientes;
use App\Helpers\Messages\CommonText;
use App\Models\BblClientes;
use App\Models\BblDiscountCode;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Aggregation\Metric\SumAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class ProcessCheckoutConfirmationListener extends HelperPago
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function handle(ProcessCheckoutConfirmationEvent $event)
    {
        try {
            $fieldValidation  = $event->arr_parametros;
            
            $payDate          = $fieldValidation["x_fecha_transaccion"];
            $stateCode        = $fieldValidation["x_cod_transaction_state"];
            $state            = $fieldValidation["x_transaction_state"];
            $epaycoReference  = $fieldValidation["x_ref_payco"];
            $authorization    = $fieldValidation["x_approval_code"];
            $bankName         = $fieldValidation["x_bank_name"];
            $totalAmount      = $fieldValidation["x_amount"];
            $credit           = null; // ?
            $transactionEmail = $fieldValidation["x_customer_email"];
            $franchise        = $fieldValidation["x_franchise"];
            $invoinceId       = explode("-",$fieldValidation["x_id_invoice"])[0];

            
            $transactionId    = $fieldValidation['x_transaction_id'];
            $currencyCode     = $fieldValidation['x_currency_code'];
            $signature        = $fieldValidation['x_signature'];


            //Validar que existe el carrito
            $searchShoppingCart = new Search();
            $searchShoppingCart->setSize(10);
            $searchShoppingCart->setFrom(0);
            
            $searchShoppingCart->addQuery(new MatchQuery('id', $invoinceId), BoolQuery::FILTER);

            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);
            if($this->shoppingCartIsPending($shoppingCartResult)){
                
                $shoppingCartData = $shoppingCartResult["data"][0];
                
                //Buscar llaves del cliente para validar signature
                $shoppingCartClientId = $shoppingCartData->clienteId;
                //TODO migrar llaves de los clientes epayco
                $clientData = BblClientes::where("id", $shoppingCartClientId)->first();
                $clientDataPasarela = BblClientesPasarelas::where("cliente_id", $shoppingCartClientId)->first();
                $clientKey = $clientDataPasarela["key_cli"];
                $calculateSignature = hash('sha256', $shoppingCartClientId . '^' . $clientKey . '^' . $epaycoReference . '^' . $transactionId . '^' . $totalAmount . '^' . $currencyCode);

                if($signature == $calculateSignature){
                    $date = new \DateTime($payDate);
                
                    $payment = [
                        "fechapago"=>$date->format("c"),
                        "estado"=>$state,
                        "referencia_epayco"=>$epaycoReference,
                        "autorizacion"=>$authorization,
                        "fechatransaccion"=>$date->format("c"),
                        "nombre_banco"=>$bankName,
                        "valortotal"=>$totalAmount,
                        "abono"=>$credit,
                        "email_transaccion"=>$transactionEmail,
                        "franquicia"=>$franchise
                    ];

                    list($shoppingCartStatus, $stateDelivery) = $this->getShoppingCartStatusByCode($stateCode);

                    if($stateCode == 1){
                        //busco numoero registrado
                        $clientDataResponse = BblClientes::find($shoppingCartClientId);

                        $sellerPhone =  $clientDataResponse->telefono;
                        $indCountry =  '+57';
                        //TODO migrar indicativo pais de los clientes
                        // $indCountry =  $clientDataResponse["ind_pais"];

                        //busco nombre de comercio
                        $search = new Search();
                        $search->setSize(1);
                        $search->setFrom(0);
                        $catalogueId = $shoppingCartData->catalogo_id;
                        $search->setSource(["nombre", "fecha", "fecha_actualizacion", "imagen", "id", "edata_estado"]);

                        $search->addQuery(new MatchQuery('cliente_id', $shoppingCartClientId), BoolQuery::FILTER);
                        $search->addQuery(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);
                        $catalogueResult = $this->consultElasticSearch($search->toArray(), "catalogo", false);
                        $catalogue = $catalogueResult["data"][0];

                        $nameShop = $catalogue->nombre;

                        $msgClient = "Usted ha realizado una compra por valor de $ ".number_format($totalAmount, 2, ',','.')." en $nameShop, Consulte el comprobante de la compra en su correo electrónico.";
                        $msgSell   = "Usted ha recibido en su DaviPlata un pago de $ ".number_format($totalAmount, 2, ',','.')." por la venta de sus productos. Consulte los movimientos de sus ventas en el App DaviPlata.";
                        $this->sendSMS($msgClient,$shoppingCartData->envio->telefono,$shoppingCartData->identificador);
                        $this->sendSMS($msgSell,$indCountry.$sellerPhone,$shoppingCartData->identificador);
                    }

                    $updateShoppingCart = $searchShoppingCart->toArray();
                    unset($updateShoppingCart["from"]);
                    unset($updateShoppingCart["size"]);
                    // Se genera la guia de entrega en caso de poseer
                    list($guide, $stateDeliveryAux) = $this->guideGeneration($shoppingCartData, $shoppingCartStatus, $epaycoReference, $stateDelivery, $clientData['url']);
                    $stateDelivery = $stateDeliveryAux;
                    $updateShoppingCart["script"] = [
                        "inline"=>"ctx._source.pagos.add(params.payment);ctx._source.estado=params.state;ctx._source.ultimo_estado_pago=params.transactionState;ctx._source.estado_entrega=params.stateDelivery;ctx._source.guia=params.guide",
                        "params"=>[
                            "payment"=>$payment,
                            "state"=>$shoppingCartStatus,
                            "transactionState"=>$state,
                            "stateDelivery"=>$stateDelivery,
                            "guide"=>$guide
                        ]
                    ];

                    $updateShoppingCart["indice"] = "shoppingcart";
                    
                    // Hacer push del objeto al arreglo de pagos dentro del carrito
                    // Actualizar el estado del carrito
                    $anukisUpdateShoppingCartResponse = $this->elasticUpdate($updateShoppingCart);

                    $this->updateSalesField($shoppingCartData);
                    if (isset($shoppingCartResult["data"][0]->codigos_descuento)) {
                        $this->handleDiscountUseCodes($shoppingCartResult["data"][0], $state);
                    }

                    if($anukisUpdateShoppingCartResponse["success"]){
                        $success = true;
                        $title_response = 'Register shoppingcart checkout';
                        $text_response = 'Register shoppingcart checkout';
                        $last_action = 'register_shoppingcart_checkout';
                        $data = [];
                    }else{

                        $success = false;
                        $title_response = 'Register shoppingcart checkout failed';
                        $text_response = 'Register shoppingcart checkout failed';
                        $last_action = 'register_shoppingcart_checkout';
                        $data = [];
                    }
                }else{
                    $success = false;
                    $title_response = 'Register shoppingcart checkout failed';
                    $text_response = 'Register shoppingcart checkout failed';
                    $last_action = 'validate_signature_shoppingcart_checkout';
                    $data = [];
                }
            }else{
                $success = false;
                $title_response = 'Shoppingcart not found';
                $text_response = 'Shoppingcart not found';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get shopping cart";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
            $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    public function shoppingCartIsPending($shoppingCartResult){
        return ($shoppingCartResult["status"] &&
            count($shoppingCartResult["data"])>0 &&
            $shoppingCartResult["data"][0]->estado != "pagado"
        && $shoppingCartResult["data"][0]->estado != "activo");
    }

    public function getShoppingCartStatusByCode($stateCode){
        $shoppingCartStatus = "activo";
        $stateDelivery = CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY;
        if($stateCode == 1){
            $shoppingCartStatus =  "pagado";
            $stateDelivery = "pendiente";
        }else if($stateCode == 3){
            $shoppingCartStatus =  "procesando_pago";
        }
        return [$shoppingCartStatus, $stateDelivery];
    }

    public function updateSalesField($shoppingCartData) {
            
        foreach($shoppingCartData->productos as $producto) {
            if(empty($producto->referencias)) {

                $queryForUpdateSales = '{"script":{"source":"ctx._source.ventas += params.ventas","params":{"ventas":' . $producto->cantidad . '}},"query":{"bool":{"filter":[{"match":{"id":{"query":' . $producto->id . '}}}]}}}';

                $queryObjectUpdateSales = json_decode($queryForUpdateSales);

                $suc = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateSales]);
                

            } else {
                foreach ($producto->referencias as $key => $reference) {

                $queryForUpdateSales = '{"script":{"source":"ctx._source.ventas += params.ventas","params":{"ventas":' . $reference->cantidad . '}},"query":{"bool":{"filter":[{"match":{"id":{"query":' . $producto->id . '}}}]}}}';

                $queryObjectUpdateSales = json_decode($queryForUpdateSales);

                $suc = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateSales]);
                
            }
            }
        }

    }

    public function sendSMS($msg,$number,$origin){
        $body=[
            "number"  => $number,
            "message" => $msg,
            "type"    => "send",
            "origin"  => "confirm-transacction-sells"
        ];
        if($origin === 'SOCIAL_SELLER'){
            $this->apiService(
                getenv("SEND_SMS"),
                (object)$body,
                "POST"
            );
        }
    }

    public function guideGeneration($shoppingCartData, $status, $epaycoReference, $stateDelivery, $pagWeb) {
        $stateDeliveryAux = $stateDelivery;
        $quote = isset($shoppingCartData->cotizacion) ? (array)$shoppingCartData->cotizacion : null;
        if($shoppingCartData->identificador=== "EPAYCO" && $status === "pagado" && $quote !== null && !empty($quote) && ($quote["tcc"] !== null || $quote[472] !== null)) {
            $catalogueQuery = new Search();
            $catalogueQuery->setSize(10);
            $catalogueQuery->setFrom(0);
            $catalogueQuery->addQuery(new MatchQuery('id', $shoppingCartData->catalogo_id), BoolQuery::FILTER);
            $catalogueResult = $this->consultElasticSearch($catalogueQuery->toArray(), "catalogo", false);
            $guideSuccess = ["status" => false];
            if (!empty($catalogueResult["data"])) {
                $this->loginApifyPrivate("elogistica");
                $guide = [];
                $logisticaService = new LogisticaService();
                $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCartData, "tcc", $catalogueResult["data"][0], $epaycoReference, $quote["tcc"], 1, $guide, "", $pagWeb);
                if ($catalogueResult["data"][0]->recogida_automatica && $quote[472] !== null && $guideSuccess["status"]) {
                    $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCartData, "472", $catalogueResult["data"][0], $epaycoReference, $quote[472], $quote[472]->id_servico, $guide,"", $pagWeb);
                    $stateDeliveryAux = "envio_programado";
                }
            }
            return [$guideSuccess["status"] ? $guide : null, $guideSuccess["status"] ? $stateDeliveryAux : $stateDelivery];
        }
        return [null, $stateDeliveryAux];
    }

    public function handleDiscountUseCodes($shoppingCartData, $stateTransacction) {
        // si ya antes se proceso alguna confirmación
        if (isset($shoppingCartData->pagos) && count($shoppingCartData->pagos) > 0) {
            $lastStateTransacction = $shoppingCartData->ultimo_estado_pago;
            if (($stateTransacction === 'Aceptada' || $stateTransacction === 'Pendiente') &&
            ($lastStateTransacction === 'Rechazada' || $lastStateTransacction === 'Fallida')) {
                $this->discountCodes($shoppingCartData->codigos_descuento, 'remove');
            } elseif (($stateTransacction === 'Rechazada' || $stateTransacction === 'Fallida') &&
            $lastStateTransacction === 'Pendiente') {
                $this->discountCodes($shoppingCartData->codigos_descuento, 'add');
            }
        } else {
            // si es la primera confirmación que llega
            if ($stateTransacction === 'Aceptada' || $stateTransacction === 'Pendiente') {
                $this->discountCodes($shoppingCartData->codigos_descuento, 'remove');
            }
        }

    }

    public function discountCodes($discountCodes, $action) {
        foreach ($discountCodes as $item) {
            $codeData = BblDiscountCode::where("nombre", $item->nombre)->first();
            if ($codeData->filtro_cantidad && $codeData->cantidad_restante > 0 && $action === 'remove') {
                $codeData->cantidad_restante = $codeData->cantidad_restante - 1;
                $codeData->save();
            } elseif ($codeData->filtro_cantidad && $action === 'add') {
                $codeData->cantidad_restante = $codeData->cantidad_restante + 1;
                $codeData->save();
            }
        }
    }
    
}
