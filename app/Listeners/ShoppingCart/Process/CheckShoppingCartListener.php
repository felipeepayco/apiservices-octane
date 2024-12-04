<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Events\ShoppingCart\Process\CheckShoppingCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use \Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use Carbon\Carbon;

class CheckShoppingCartListener extends HelperPago
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

    public function handle(CheckShoppingCartEvent $event)
    {
        try {

            $searchShoppingCart = new Search();
            $searchShoppingCart->setSize(5000);
            $searchShoppingCart->setFrom(0);
            $searchShoppingCart->addQuery(new MatchQuery('estado', 'activo'), BoolQuery::FILTER);

            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);
            $data = [];
            
            foreach ($shoppingCartResult["data"] as $value) {
                array_push($data, $value);
           }

            foreach ($data as $key) {

                $fecha = $key->fecha;
                $id = $key->id;

                $date = new \DateTime($fecha);
                $now = new \DateTime((date("c")));

                $interval = $date->diff($now);

                // mayor a 2 minutos
                if ($interval->h >= 1) {
                    
                    // coloca en estado abandonado el carrito con una duracion mayor a 3 horas
                    $query = '{"script":{"source":"ctx._source.estado = params.estado","params":{"estado":"abandonado"}},"query":{"bool":{"must":[{"match":{"id":"' . $id . '"}}]}}}';

                    $queryObjectUpdateEstado = json_decode($query);

                    $anukisResponse = $this->updateRawQueryElastic(["indice" => "shoppingcart", "data" => $queryObjectUpdateEstado]);


                    $products = $key->productos;

                    foreach ($products as $product) {
                        $productId = $product->id;
                        isset($product->cantidad) ? ($productQuantity = $product->cantidad) : null;

                        // query para buscar por indice producto actualizar el stock inicial de productos sin referencia
                        if (empty($product->referencias)) {
                            $queryQuantity = '{"script":{"source":"ctx._source.disponible += params.cantidad","params":{"cantidad":' . $productQuantity . '}},"query":{"bool":{"must":[{"match":{"id":' . $productId . '}}]}}}';

                            $queryObjectUpdateStock = json_decode($queryQuantity);

                            $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);
                            
                        }

                        if (!empty($product->referencias)) {
                            $references = $product->referencias;


                            foreach ($references as $key => $reference) {

                                if (isset($reference->cantidad) && isset($reference->id)) {

                                    $queryForUpdateStockRefences = '{"query":{"bool":{"filter":[{"match":{"id":' . $productId . '}}]}},"script":{"inline":"if(ctx._source.referencias !== null) {def targets = ctx._source.referencias.findAll(producto -> producto.id == params.id); for(producto in targets) { producto.disponible += params.cantidad }}","params":{"id":' . $reference->id . ',     "cantidad":' . $reference->cantidad . '}}}';

                                    // con el raw query aca le sumo al stock del producto la cantidad del shopping 
                                    $queryObjectUpdateStockReferences = json_decode($queryForUpdateStockRefences);
                                    $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStockReferences]);
                                }
                                
                            }
                        }
                    }
                }
            }
            $success = true;
            $title_response = "Successful updated shopping cart";
            $text_response = "successful updated shopping cart";
            $last_action = "shopping cart updated";
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new shopping cart";
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
        $arr_respuesta['data'] = [];

        return $arr_respuesta;
    }
}
