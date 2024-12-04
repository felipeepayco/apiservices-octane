<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Models\Clientes;
use \Illuminate\Http\Request;
use App\Events\ShoppingCart\Process\ProcessLoadPickupEvent;
use App\Listeners\Services\LogisticaService;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;

use App\Helpers\Messages\CommonText;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Aggregation\Metric\SumAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;


class ProcessLoadPickupListener extends HelperPago
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

    public function handle(ProcessLoadPickupEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            $operator = $fieldValidation["operator"];
            $date = $fieldValidation["date"];
            $note = isset($fieldValidation["note"]) ? $fieldValidation["note"] : "";

            $clientData = Clientes::where("id", $clientId)->first();

            //Validar que exista el carrito 
            $searchShoppingCart = $this->searchShoppingCart($id,$clientId);
            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);

            if($shoppingCartResult["status"]){
                if(count($shoppingCartResult["data"])>0) {
                    $shoppingCart = $shoppingCartResult["data"][0];
                    $catalogueQuery = new Search();
                    $catalogueQuery->setSize(10);
                    $catalogueQuery->setFrom(0);
                    $catalogueQuery->addQuery(new MatchQuery('id', $shoppingCart->catalogo_id), BoolQuery::FILTER);
                    $catalogueResult = $this->consultElasticSearch($catalogueQuery->toArray(), "catalogo", false);
                    //logica para crear guia y recogida
                    $epaycoReference = $this->findRefEpayco($shoppingCart);
                    $quote = isset($shoppingCart->cotizacion) ? (array)$shoppingCart->cotizacion : null;
                    $guide = isset($shoppingCart->guia) ? (array)$shoppingCart->guia : [];
                    $pickup = [];
                    $this->loginApifyPrivate("elogistica");
                    $guideSuccess = ["status" => true];
                    $logisticaService = new LogisticaService();
                    if ($operator === '472') {
                        $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCart, $operator, $catalogueResult["data"][0], $epaycoReference, $quote[$operator], $quote[472] !== null ? $quote[472]->id_servico : 1, $guide, $note, $clientData['pagweb']);
                    } else {
                        $pickup[$operator] = $this->handleProgramPickup($shoppingCart, $operator, $catalogueResult["data"][0], $guide[$operator], $note, $date);
                    }

                    $inline = "ctx._source.estado_entrega=params.stateDelivery;";
                    $params = [
                        "stateDelivery"=>"envio_programado"
                    ];
                    if (empty($pickup)) {
                        $inline =$inline."ctx._source.guia=params.guide";
                        $params["guide"] = $guide;
                    } else {
                        $inline =$inline."ctx._source.entrega=params.pickup";
                        $params["pickup"] = $pickup;
                    }
                    

                    $updateShoppingCart = $searchShoppingCart->toArray();
                    unset($updateShoppingCart["from"]);
                    unset($updateShoppingCart["size"]);
                    $updateShoppingCart["script"] = [
                        "inline"=>$inline,
                        "params"=>$params
                    ];
                    $updateShoppingCart["indice"] = "shoppingcart";

                    $anukisUpdateShoppingCartResponse = ["success" => false];
                    if ($guideSuccess["status"]) {
                        $anukisUpdateShoppingCartResponse = $this->elasticUpdate($updateShoppingCart);
                    }

                    if($anukisUpdateShoppingCartResponse["success"]){
                        $success = true;
                        $title_response = 'pickup Shopping cart successfull';
                        $text_response = 'pickup Shopping cart successfull';
                        $last_action = 'pickup_shoppingcart';
                        $data = empty($pickup) ? $guide : $pickup;
                    }else{
                        $success = false;
                        $title_response = 'Register shoppingcart pickup failed';
                        $text_response = 'Register shoppingcart pickup failed';
                        $last_action = 'register_shoppingcart_pickup';
                        $data = $guideSuccess;
                    }
                    
                } else{
                    $success = false;
                    $title_response = 'Shopping cart not found';
                    $text_response = 'Shopping cart not found';
                    $last_action = 'consult_shopping_cart';
                    $data = [];    
                }
            } else{
                $success = false;
                $title_response = 'Unsuccessfully consult shopping cart';
                $text_response = 'Unsuccessfully consult shopping cart';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }
            

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error '.$exception->getMessage();
            $text_response = "Error program pickup shopping cart";
            $last_action = 'fetch data from database'.$exception->getLine();
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

    public function searchShoppingCart($id,$clientId){
        $searchShoppingCart = new Search();
        $searchShoppingCart->setSize(1);
        $searchShoppingCart->setFrom(0);
        $searchShoppingCart->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
        $searchShoppingCart->addQuery(new MatchQuery('estado', "pagado"), BoolQuery::FILTER);
        $searchShoppingCart->addQuery(new MatchQuery('clienteId', $clientId), BoolQuery::FILTER);
        return $searchShoppingCart;
    }

    public function findRefEpayco($shoppingCartData) {
        foreach ($shoppingCartData->pagos as $item) {
            if ($item->estado === "Aceptada") {
                return $item->referencia_epayco;
            }

        }
        return "";
    }

    public function handleProgramPickup($shoppingCart, $operator, $catalogue, $guide, $note, $date){
        $time = time();
        $bodyPickup = [
            "operador" => $operator,
            "id_operacion_epayco" => $guide->data->id_operacion_epayco,
            "id_configuracion" => $catalogue->configuracion_recogida_id,
            "fecha_recogida" => $date,
            "hora_inicial_recogida" => date("a", $time) === "am" ? "09:00:00" : "12:00:00",
            "hora_final_recogida" => date("a", $time) === "am" ? "12:00:00" : "19:00:00",
            "observaciones" => $note
        ];
        $response = $this->elogisticaRequest($bodyPickup, "/api/v1/recogida");
        $response["fecha_registro"] = date("d/m/Y h:i:s a", $time);
        return $response;
    }
}
