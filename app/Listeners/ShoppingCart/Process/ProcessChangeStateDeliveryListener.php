<?php

namespace App\Listeners\ShoppingCart\Process;

use \Illuminate\Http\Request;
use App\Events\ShoppingCart\Process\ProcessChangeStateDeliveryEvent;
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


class ProcessChangeStateDeliveryListener extends HelperPago
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

    public function handle(ProcessChangeStateDeliveryEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            $newStateDelivery = $fieldValidation["newStateDelivery"];

            //Validar que exista el carrito 
            $searchShoppingCart = $this->searchShoppingCart($id,$clientId);
            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);

            if($shoppingCartResult["status"]){
                if(count($shoppingCartResult["data"])>0) {
                    $shoppingCart = $shoppingCartResult["data"][0];

                    if($shoppingCart->estado === "pagado") {
                        $updateShoppingCart = $searchShoppingCart->toArray();
                        unset($updateShoppingCart["from"]);
                        unset($updateShoppingCart["size"]);

                        $updateShoppingCart["script"] = [
                            "inline"=>"ctx._source.estado_entrega=params.newStateDelivery",
                            "params"=>[
                                "newStateDelivery"=>$newStateDelivery
                            ]
                        ];
                        $updateShoppingCart["indice"] = "shoppingcart";

                        $anukisUpdateShoppingCartResponse = $this->elasticUpdate($updateShoppingCart);
                        
                        if($anukisUpdateShoppingCartResponse["success"]){
                            $success = true;
                            $title_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                            $text_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                            $last_action = 'update_shoppingcart';
                            $data = [];
                        }else{
                            
                            $success = false;
                            $title_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                            $text_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                            $last_action = 'update_shoppingcart';
                            $data = [];
                        }

                    } else{
                        $success = false;
                        $title_response = 'Shoppingcart is not pay accepted';
                        $text_response = $shoppingCart->estado;
                        $last_action = 'consult_shopping_cart';
                        $data = [];    
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
            $title_response = 'Error';
            $text_response = "Error update state delivery shopping cart";
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

    public function searchShoppingCart($id,$clientId){
        $searchShoppingCart = new Search();
        $searchShoppingCart->setSize(1);
        $searchShoppingCart->setFrom(0);
        $searchShoppingCart->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
        $searchShoppingCart->addQuery(new MatchQuery('clienteId', $clientId), BoolQuery::FILTER);
        return $searchShoppingCart;
    }
}
