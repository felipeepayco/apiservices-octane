<?php
namespace App\Listeners\Catalogue\Process\Product;


use App\Events\CatalogueProductActiveInactiveElasticEvent;
use App\Events\ConsultSellListEvent;
use App\Events\CatalogueProductNewEvent;
use App\Events\ValidationGeneralSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use App\Models\CatalogoProductos;
use App\Models\CatalogoCategorias;
use App\Models\CatalogoProductosCategorias;

use App\Models\CompartirCobro;
use App\Models\FilesCobro;
use App\Models\Trm;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class CatalogueProductActiveInactiveElasticListener extends HelperPago {

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueProductActiveInactiveElasticEvent $event)
    {
               
        try{

                $data = $event->arr_parametros;
                $idProd = $data['id'];
                $clientId = $data['clientId'];

                $search = new Search();
                $search->setSize(5000);
                $search->setFrom(0);

                $search->addQuery(new MatchQuery('cliente_id',  $clientId), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery('id', $idProd), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery('activo', true), BoolQuery::FILTER);

                $query = $search->toArray();
                $invoices = $this->consultElasticSearch($query, "producto", false);

                //Verifica si el producto Activo existe
                if(count($invoices['data'])>0){

                    unset($query["from"]);
                    unset($query["size"]);
                    $query["script"] = [
                        "source"=>"ctx._source.activo = false",
                        "params"=>[
                            "id"=>(int)$idProd,
                        ]
                    ];

                    
                    $query["indice"] = "producto";

                    $this->validateLastProductInCatalogue($invoices['data'],$clientId);
                    $anukisUpdateProductoResponse = $this->elasticUpdate($query);

                    if($anukisUpdateProductoResponse["success"]){
                        $anukisResponseData = json_decode($anukisUpdateProductoResponse["data"]->body);
                        if($anukisResponseData->updated > 0){

                            $categoryUpdated = true;
                            $this->deleteCatalogueRedis($invoices['data'][0]->catalogo_id);
                        }else{
                            $categoryUpdated = false;
                        }
                    }else{
                        $categoryUpdated = false;
                    }

                    if($categoryUpdated){
                        $success= true;
                        $title_response = 'Successful inactive product';
                        $text_response = 'successful inactive product';
                        $last_action = 'inactive product';
                        $data = [
                            "success" => true,
                            "titleResponse" => "Successful inactive product",
                            "textResponse" => "successful inactive product",
                            "lastAction" => "active product",
                            "data" => [
                                "active" => false
                            ]
                            ];
                    }else{
                        $success= false;
                        $title_response = 'Error inactive product';
                        $text_response = 'Error inactive product, product not found';
                        $last_action = 'inactive product';
                        $data = [];
                    }
                    
                }else{
                    //Verifica si el producto Inactivo existe
                    $search = new Search();
                    $search->setSize(5000);
                    $search->setFrom(0);
    
                    $search->addQuery(new MatchQuery('cliente_id',  $clientId), BoolQuery::FILTER);
                    $search->addQuery(new MatchQuery('id', $idProd), BoolQuery::FILTER);
                    $search->addQuery(new MatchQuery('activo', false), BoolQuery::FILTER);
                    $query = $search->toArray();
                    $invoices = $this->consultElasticSearch($query, "producto", false);
                    if(count($invoices['data'])>0){
                        unset($query["from"]);
                        unset($query["size"]);
                        $query["script"] = [
                            "source"=>"ctx._source.activo = true",
                            "params"=>[
                                "id"=>(int)$idProd,
                            ]
                        ];
    
                        
                        $query["indice"] = "producto";
    
                        $this->validateLastProductInCatalogue($invoices['data'],$clientId);
                        $anukisUpdateProductoResponse = $this->elasticUpdate($query);
    
                        if($anukisUpdateProductoResponse["success"]){
                            $anukisResponseData = json_decode($anukisUpdateProductoResponse["data"]->body);
                            if($anukisResponseData->updated > 0){
    
                                $categoryUpdated = true;
                               // $this->deleteCatalogueRedis($invoices['data'][0]->catalogo_id);
                            }else{
                                $categoryUpdated = false;
                            }
                        }else{
                            $categoryUpdated = false;
                        }
    
                        if($categoryUpdated){
                            $success= true;
                            $title_response = 'Producto activado exitosamente';
                            $text_response = 'Producto activado exitosamente';
                            $last_action = 'active product';
                            $data = [
                                "success" => true,
                                "titleResponse" => "Successful active product",
                                "textResponse" => "successful active product",
                                "lastAction" => "active product",
                                "data" => [
                                    "active" => true
                                ]
                                ];
                        }else{
                            $success= false;
                            $title_response = 'Producto no pudo ser activado';
                            $text_response = 'Producto no pudo ser activado';
                            $last_action = 'active product';
                            $data = [];
                        }

                    }else{
                     //Resultado si no encuentra el producto
                    $success = false;
                    $title_response = 'Error';
                    $text_response = "Error activeInactive product, product not found";
                    $last_action = 'fetch data from database';
                    $error = $this->getErrorCheckout('E0100');
                    $validate = new Validate();
                    $validate->setError($error->error_code, $error->error_message);
                    $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                        $validate->errorMessage);
                }                
            }
           

        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Producto no pudo ser activado";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);

        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function validateLastProductInCatalogue($product,$clientId){
        if(isset($product[0]) && isset($product[0]->origen) && $product[0]->origen=="epayco"){
            $search = new Search();
            $search->setSize(5000);
            $search->setFrom(0);

            $search->addQuery(new MatchQuery('cliente_id',  $clientId), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('catalogo_id', $product[0]->catalogo_id), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);

            $query = $search->toArray();
            $products = $this->consultElasticSearch($query, "producto", false);

            if(count($products["data"])==1){
                $search = new Search();
                $search->addQuery(new MatchQuery('id', $product[0]->catalogo_id), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
                $updateData = $search->toArray();

                $inlines = [
                    "ctx._source.progreso='completado'",
                ];

                $updateData["script"] = ["inline"=>implode(";",$inlines)];
                $updateData["indice"] = "catalogo";

                $this->elasticUpdate($updateData);
            }
        }
    }

    private function deleteCatalogueRedis ($catalogueId){
        $redis =  app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_'.$catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_'.$catalogueId);
        }
    }
}

