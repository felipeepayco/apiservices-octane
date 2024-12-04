<?php

namespace App\Listeners\Catalogue\Process;


use App\Events\Catalogue\Process\CatalogueDeleteEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;

class CatalogueDeleteListener extends HelperPago
{

    /**
     * CatalogueDeleteListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param CatalogueDeleteEvent $event
     * @return mixed
     */
    public function handle(CatalogueDeleteEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            
            $searchCatalogueExist = new Search();
            $searchCatalogueExist->setSize(500);
            $searchCatalogueExist->setFrom(0);
            $searchCatalogueExist->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
            $searchCatalogueExist->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $searchCatalogueExist->addQuery(new MatchQuery('estado', true), BoolQuery::FILTER);

            
            $catalogueExistResult = $this->consultElasticSearch($searchCatalogueExist->toArray(),"catalogo",false);

            if(count($catalogueExistResult["data"])>0){
                $search = new Search();
                $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
                $updateData = $search->toArray();
                
                
                $inlines = [ 
                    "ctx._source.estado=false",
                    "if(ctx._source.categorias !== null) {for(category in ctx._source.categorias) { category.estado = false }}",
                ];

                $updateData["script"] = ["inline"=>implode(";",$inlines)];
                $updateData["indice"] = "catalogo";

                $anukisResponse = $this->elasticUpdate($updateData);

                if($anukisResponse["success"]){
                    $catalogueDelete = $this->deleteCatalogueRedis($anukisResponse, $id);
                }else{
                    $catalogueDelete = false;
                }
            }else{
                $catalogueDelete = false;
            }

            if ($catalogueDelete) {
                $this->disabledCatalogueProducts($id,$clientId);
                $success = true;
                $title_response = 'Successful delete catalogue';
                $text_response = 'successful delete catalogue';
                $last_action = 'delete catalogue';
                $data = [];
            } else {
                $success = false;
                $title_response = 'Error delete catalogue';
                $text_response = 'Error delete catalogue, catalogue not found';
                $last_action = 'delete catalogue';
                $data = [];
            }


        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado ".$exception->getMessage();
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

    private function deleteCatalogueRedis ($anukisResponse, $id){
        $anukisResponseData = json_decode($anukisResponse["data"]->body);
        if($anukisResponseData->updated > 0){
            $catalogueDelete = true;
            $redis =  app('redis')->connection();
            $exist = $redis->exists('vende_catalogue_'.$id);
            if ($exist) {
                $redis->del('vende_catalogue_'.$id);
            }
        }else{
            $catalogueDelete = false;
        }
        return $catalogueDelete;
    }

    private function disabledCatalogueProducts($id,$clientId){
        $search = new Search();
        $search->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::MUST);
        $search->addQuery(new MatchQuery('catalogo_id', $id), BoolQuery::MUST);
        $query = $search->toArray();
        $query["script"] = [
            "inline"=>"ctx._source.categorias = [1];ctx._source.estado = 0",
        ];
        $query["indice"] = "producto";
        $this->elasticUpdate($query);

    }
}