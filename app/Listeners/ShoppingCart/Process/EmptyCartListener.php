<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Events\ShoppingCart\Process\EmptyCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\ShoppingCart;
use App\Models\CatalogoProductos;
use \Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Search;
use Carbon\Carbon;

class EmptyCartListener extends HelperPago
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

    public function handle(EmptyCartEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;

            $id = isset($fieldValidation["id"]) ? $fieldValidation["id"] : 0;
            $clientId = isset($fieldValidation["clientId"]) ? $fieldValidation["clientId"] : 0;

            if (trim($id) != '' && $clientId!=0) {
                $search = new Search();
                $search->setSize(5000);
                $search->setFrom(0);
    
                $search->addQuery(new MatchQuery('id',  $id), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery('clienteId',  $clientId), BoolQuery::FILTER);
                $queryShopping = $search->toArray();
    
                $invoices = $this->consultElasticSearch($queryShopping, "shoppingcart", false);
                if(count($invoices['data'])>0){


                    foreach($invoices['data'] as $key => $value){
                        foreach ($value->productos as $kp => $product) {
                            $productId = $product->id;

                            $search = new Search();
                            $search->setSize(5000);
                            $search->setFrom(0);

                            $search->addQuery(new MatchQuery('cliente_id',  $clientId), BoolQuery::FILTER);
                            $search->addQuery(new MatchQuery('id', $productId), BoolQuery::FILTER);
                            $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);
                            $query = $search->toArray();
                            if(!isset($product->referencias)){

                                    $invoices = $this->consultElasticSearch($query, "producto", false);
                                    
                                    $quantity = isset($product->cantidad) ? $product->cantidad : null;
    
                                    unset($query["from"]);
                                    unset($query["size"]);
                                    $query["script"] = [
                                        "inline"=>"ctx._source.disponible += params.quantity;",
                                        "params"=>[
                                            "quantity" => $quantity
                                            ]
                                    ];
    
                                    $query["indice"] = "producto";
                                    $anukisUpdateProductoResponse = $this->elasticUpdate($query);
    
                                    if($anukisUpdateProductoResponse["success"]){
                                        $anukisResponseData = json_decode($anukisUpdateProductoResponse["data"]->body);
                                        if($anukisResponseData->updated > 0){
                                            $categoryUpdated = true;
                                        }else{
                                            $categoryUpdated = false;
                                        }
                                    }else{
                                        $categoryUpdated = false;
                                    }
                
                                    if($categoryUpdated){
                                        $success= true;
                                        $title_response = 'Successful cart emptied';
                                        $text_response = 'successful cart emptied';
                                        $last_action = 'cart emptied';
                                        $data = [];
                                    }else{
                                        $success= false;
                                        $title_response = 'Error empty cart';
                                        $text_response = 'Error empty cart';
                                        $last_action = 'delete sell';
                                        $data = [];
                                    }
                            }else{
                                if(count($product->referencias)>0){

                                    foreach ($product->referencias as $key => $value) {
                                        $invoices = $this->consultElasticSearch($query, "producto", false);

                                        $quantity = isset($value->cantidad) ? $value->cantidad : null;
                                        $id = $value->id;
                                        unset($query["from"]);
                                        unset($query["size"]);
                                        
                                        $query["script"] = [
                                            "inline"=>"if(ctx._source.referencias !== null) {def targets = ctx._source.referencias.findAll(referencia -> referencia.id == params.id); for(referencia in targets) { referencia.disponible += params.quantity }}",
                                            "params"=>[
                                                "id"=> $id,
                                                "quantity" => $quantity
                                                ]
                                        ];
    
                                        $query["indice"] = "producto";
                                        $anukisUpdateProductoResponse = $this->elasticUpdate($query);
    
                                        if($anukisUpdateProductoResponse["success"]){
                                            $anukisResponseData = json_decode($anukisUpdateProductoResponse["data"]->body);
                                            if($anukisResponseData->updated > 0){
                                                $categoryUpdated = true;
                                            }else{
                                                $categoryUpdated = false;
                                            }
                                        }else{
                                            $categoryUpdated = false;
                                        }
                    
                                        if($categoryUpdated){
                                            $success= true;
                                            $title_response = 'Successful cart emptied';
                                            $text_response = 'successful cart emptied';
                                            $last_action = 'cart emptied';
                                            $data = [];
                                        }else{
                                            $success= false;
                                            $title_response = 'Error empty cart';
                                            $text_response = 'Error empty cart';
                                            $last_action = 'delete sell';
                                            $data = [];
                                        }
                                    }
                                }

                            }
                            
                        }

                    }


                    unset($queryShopping["from"]);
                    unset($queryShopping["size"]);
                    $queryShopping["indice"] = "shoppingcart";
                    $queryShopping["script"] = [
                        "inline"=>"ctx._source.estado = params.estado;",
                        "params"=>[
                            "estado" => 'eliminado'
                            ]
                    ];
                    $updateState = $this->elasticUpdate($queryShopping);

                    if($updateState["success"]){
                        $anukisResponseData = json_decode($updateState["data"]->body);
                        if($anukisResponseData->updated > 0){
                            $Updated = true;
                        }else{
                            $Updated = false;
                        }
                    }else{
                        $Updated = false;
                    }

                    if($Updated){
                        $success= true;
                        $title_response = 'Successful cart emptied';
                        $text_response = 'successful cart emptied';
                        $last_action = 'cart emptied';
                        $data = [];
                    }else{
                        $success= false;
                        $title_response = 'Error empty cart';
                        $text_response = 'Error empty cart';
                        $last_action = 'delete sell';
                        $data = [];
                    }

                }else{
                    $success = false;
                    $title_response = 'ID not found';
                    $text_response = "Error finding shopping cart";
                    $last_action = 'fetch data from database';
                    $error = $this->getErrorCheckout('E0100');
                    $validate = new Validate();
                    $validate->setError($error->error_code, $error->error_message);
                    $data = [];
                }

            }else{
                $success = false;
                $title_response = 'ID not found';
                $text_response = "Error finding shopping cart";
                $last_action = 'fetch data from database';
                $error = $this->getErrorCheckout('E0100');
                $validate = new Validate();
                $validate->setError($error->error_code, $error->error_message);
                $data = [];
            }


        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'ID not found';
            $text_response = "Error finding shopping cart";
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
}
