<?php
namespace App\Listeners\Catalogue\Process\Category;


use App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesDeleteEvent;
use App\Events\Catalogue\Process\Category\ConsultSellDeleteEvent;
use App\Events\Catalogue\Process\Category\ConsultSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\CatalogoCategorias;
use App\Models\Cobros;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;

class ConsultCatalogueCategoryDeleteListener extends HelperPago {

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
    public function handle(ConsultCatalogueCategoriesDeleteEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $id=$fieldValidation["id"];

            $searchCategoryExist = new Search();
            $searchCategoryExist->setSize(10);
            $searchCategoryExist->setFrom(0);
            $searchCategoryExist->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::MUST);

            //preparar nested query
            $matchQueryCategoryId = new MatchQuery('categorias.id', $id);
            $matchQueryCategoryState = new MatchQuery('categorias.estado', true);
            
            $boolQuery = new BoolQuery();
            $boolQuery->add($matchQueryCategoryId);
            $boolQuery->add($matchQueryCategoryState);
            $nestedQuery = new NestedQuery(
                'categorias',
                $boolQuery
            );

            // fin preparar nested query
            $searchCategoryExist->addQuery($nestedQuery);
            $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray(), "catalogo", false);  


            if(count($searchCategoryExistResult["data"])>0){

                $updateCategory = $searchCategoryExist->toArray();
                unset($updateCategory["from"]);
                unset($updateCategory["size"]);

                //script del update_by_query con la categoria nueva como parametro
                $inlines = [
                    "if(ctx._source.categorias !== null) {def targets = ctx._source.categorias.findAll(category -> category.id == params.id); for(category in targets) { category.estado = false }}"
                ];

                if($this->validateLastCategory($searchCategoryExistResult)){
                    array_unshift($inlines,"ctx._source.progreso='completado'");
                }

                $updateCategory["script"] = [
                    "inline"=>implode(";",$inlines),
                    "params"=>["id"=>(int)$id]
                ];

                $updateCategory["indice"] = "catalogo";

                $anukisUpdateCategoryResponse = $this->elasticUpdate($updateCategory);

                if($anukisUpdateCategoryResponse["success"]){
                    $anukisResponseData = json_decode($anukisUpdateCategoryResponse["data"]->body);
                    if($anukisResponseData->updated > 0){
                        $categoryUpdated = true;
                    }else{
                        $categoryUpdated = false;
                    }
                }else{
                    $categoryUpdated = false;
                }

                if($categoryUpdated){
                    $this->deleteCatalogueRedis($searchCategoryExistResult["data"][0]->id);

                    $this->migrateProductsToGeneralCategory($id);
                    $success= true;
                    $title_response = 'Successful delete category';
                    $text_response = 'successful delete category';
                    $last_action = 'delete category';
                    $data = [];
                }else{
                    $success= false;
                    $title_response = 'Error delete category';
                    $text_response = 'Error delete category, category not found';
                    $last_action = 'delete sell';
                    $data = [];
                }
            }else{
                $success= false;
                $title_response = 'Error delete category';
                $text_response = 'Error delete category, category not found';
                $last_action = 'delete sell';
                $data = [];
            }

        }catch (\Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los cobros con los parametros datos";
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

    private function deleteCatalogueRedis ($catalogueId){
        $redis =  app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_'.$catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_'.$catalogueId);
        }
    }

    private function validateLastCategory($catalogue){

        $categories = $catalogue["data"][0]->categorias;
        $countCategories = 0;

        if(isset($catalogue["data"][0]->procede) && $catalogue["data"][0]->procede == "epayco"){
            foreach($categories as $category){
                if($category->id !== 1 && $category->estado){
                    $countCategories=$countCategories+1;
                }
            }

            return $countCategories===1;
        }
    }

    private function migrateProductsToGeneralCategory($categoryId){
        $search = new Search();
        $search->addQuery(new MatchQuery('categorias', $categoryId), BoolQuery::FILTER);
        $updateData = $search->toArray();
        
        $inlines = [ 
            "ctx._source.categorias=[1]",
            "ctx._source.activo=false"
        ];

        $updateData["script"] = ["inline"=>implode(";",$inlines)];
        $updateData["indice"] = "producto";
        
        $this->elasticUpdate($updateData);
    }
}