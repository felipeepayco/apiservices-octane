<?php
namespace App\Listeners\Catalogue\Process\Product;


use App\Events\CatalogueProductDeleteElasticEvent;
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

class CatalogueProductDeleteElasticListener extends HelperPago {

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
    public function handle(CatalogueProductDeleteElasticEvent $event)
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
                $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);

                $query = $search->toArray();
                $invoices = $this->consultElasticSearch($query, "producto", false);

                
                if(count($invoices['data'])>0){

                    unset($query["from"]);
                    unset($query["size"]);
                    $query["script"] = [
                        "source"=>"ctx._source.estado = 0",
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
                        $title_response = 'Successful delete product';
                        $text_response = 'successful delete product';
                        $last_action = 'delete product';
                        $data = [
                            "success" => true,
                            "titleResponse" => "Successful delete product",
                            "textResponse" => "successful delete product",
                            "lastAction" => "delete product",
                            "data" => []
                        ];
                    }else{
                        $success= false;
                        $title_response = 'Error delete product';
                        $text_response = 'Error delete product, product not found';
                        $last_action = 'delete sell';
                        $data = [];
                    }
                    
                }else{
                    $success = false;
                    $title_response = 'Error';
                    $text_response = "Error delete product, product not found";
                    $last_action = 'fetch data from database';
                    $error = $this->getErrorCheckout('E0100');
                    $validate = new Validate();
                    $validate->setError($error->error_code, $error->error_message);
                    $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                        $validate->errorMessage);
                }                
                
           

        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error delete product";
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

