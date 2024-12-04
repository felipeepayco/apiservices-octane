<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Events\ShoppingCart\Process\CheckEmptyCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\ShoppingCart;
use App\Models\CatalogoProductos;
use \Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Search;
use Carbon\Carbon;

class CheckEmptyCartListener extends HelperPago
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

    public function handle(CheckEmptyCartEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;

            $id = isset($fieldValidation["id"]) ? $fieldValidation["id"] : 0;

            if ($id > 0) {

                if ($id > 0) {
                
                    $search = new Search();
                    $search->setSize(5000);
                    $search->setFrom(0);
                    $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
                    $shoppingcartResult = $this->searchGeneralElastic(["indice" => "shoppingcart", "data" => $search->toArray()]);
    
                    if ($shoppingcartResult["data"] && isset($shoppingcartResult["data"]->hits->hits[0]->_id)) {
                        
                        $estado_shoppingcart = $shoppingcartResult["data"]->hits->hits[0]->_source->estado;
    
    
                        if($estado_shoppingcart === "activo") {
    
                            $arr_respuesta['success'] = "success";
                                        $arr_respuesta['titleResponse'] = "Successful shopping cart found";
                                        $arr_respuesta['textResponse'] = "Successful  shopping cart found";
                                        $arr_respuesta['lastAction'] = "Successful  shopping cart found";
                                        $arr_respuesta['data'] =  $shoppingcartResult["data"]->hits->hits[0]->_source;
    
                                        return $arr_respuesta;
                                    }
    
                            
    
                        } else {

                                        $arr_respuesta['success'] = "Shopping cart ${estado_shoppingcart}";
                                        $arr_respuesta['titleResponse'] = "Shopping cart ${estado_shoppingcart}";
                                        $arr_respuesta['textResponse'] = "Shopping cart ${estado_shoppingcart}";
                                        $arr_respuesta['lastAction'] = "Shopping cart ${estado_shoppingcart}";
                                        $arr_respuesta['data'] =  "";
    
                                        return $arr_respuesta;
                                    }
    
                        }
                    }
                    $arr_respuesta['success'] = false;
                    $arr_respuesta['titleResponse'] = "shoppingcart not found";
                    $arr_respuesta['textResponse'] = "shoppingcart not found";
                    $arr_respuesta['lastAction'] = "searching shoppingcart";
                    $arr_respuesta['data'] = [];

                    return $arr_respuesta;


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
