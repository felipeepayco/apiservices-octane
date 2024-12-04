<?php
namespace App\Listeners\Catalogue\Process;

use App\Helpers\Validation\CommonValidation;
use App\Listeners\Services\VendeConfigPlanService;
use App\Events\Catalogue\Process\CatalogueInactiveEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;

class CatalogueInactiveListener extends HelperPago
{

        /**
         * CatalogueInactiveListener constructor.
         * @param Request $request
         */
        public function __construct(Request $request)
        {

                parent::__construct($request);
        }

        /**
         * @param CatalogueInactiveEvent $event
         * @return mixed
         */
        public function handle(CatalogueInactiveEvent $event)
        {
                // Colocar todos los catalogos de ese cliente en estado Inactivo
                try
                {
                        $fieldValidation = $event->arr_parametros;
                        $origin = CommonValidation::getFieldValidation($fieldValidation,"origin");
                        $suspended = CommonValidation::getFieldValidation($fieldValidation,"suspended", false);
                        $clientId = $fieldValidation["clientId"];

                        $search = new Search();
                        $search->addQuery(new MatchQuery('cliente_id', $clientId) , BoolQuery::FILTER);
                        $updateData = $search->toArray();
                        if($origin=="epayco"  && $suspended){
                                $vendeConfigPlan = new VendeConfigPlanService();
                                $plan = $vendeConfigPlan->getPlanActiveAndDateToday($clientId);
                                if (is_null($plan)) {
                                        $inlines = ["ctx._source.estado_plan='suspendido'"];
                                }
                        } else {
                                $inlines = ["ctx._source.activo=false"];
                        }

                        $updateData["script"] = ["inline" => implode(";", $inlines) ];
                        $updateData["indice"] = "catalogo";

                        $anukisResponse = $this->elasticUpdate($updateData);

                        if ($anukisResponse["success"])
                        {
                                if(!$suspended){
                                    $this->inactiveCategories($clientId);
                                    $this->inactiveProducts($clientId);
                                }
                                $success = true;
                                $title_response = 'Successful inactive catalogue';
                                $text_response = 'successful inactive catalogue';
                                $last_action = 'inactive catalogue';
                                $data = [];
                        }
                        else
                        {
                                $success = false;
                                $title_response = 'Error inactive catalogue';
                                $text_response = 'Error inactive catalogue, catalogue not found';
                                $last_action = 'inactive catalogue';
                                $data = [
                                    "totalErrors"=> 1,
                                    "errors"=> [
                                        [
                                            "codError"=> 500,
                                            "errorMessage"=> "Error inactive catalogue, catalogue not found"
                                        ]
                                    ]
                                ];
                        }

                }
                catch(\Exception $exception)
                {
                        $success = false;
                        $title_response = 'Error';
                        $text_response = "Error inesperado " . $exception->getMessage();
                        $last_action = 'fetch data from database';
                        $error = $this->getErrorCheckout('E0100');
                        $validate = new Validate();
                        $validate->setError($error->error_code, $error->error_message);
                        $data = array(
                                'totalErrors' => $validate->totalerrors,
                                'errors' => $validate->errorMessage
                        );
                }

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
        }

        // Colocar categorias de ese catalogo en Inactivo
        

        private function inactiveCategories($clientId)
        {

                try
                {
                        
                        
                        $searchCategoryExist = new Search();
                        $searchCategoryExist->setSize(500);
                        $searchCategoryExist->setFrom(0);
                        $searchCategoryExist->addQuery(new MatchQuery('cliente_id', $clientId) , BoolQuery::FILTER);
                        //preparar nested query
                        $matchQueryCategoryState = new MatchQuery('categorias.estado', true);
                        
                        $boolQuery = new BoolQuery();
                        
                        $boolQuery->add($matchQueryCategoryState);
                        
                        $nestedQuery = new NestedQuery('categorias', $boolQuery);

                        // fin preparar nested query
                        $searchCategoryExist->addQuery($nestedQuery);
                        
                        $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray() , "catalogo", false);
                        
                        if (count($searchCategoryExistResult["data"]) > 0)
                        {

                                $updateCategory = $searchCategoryExist->toArray();
                                unset($updateCategory["from"]);
                                unset($updateCategory["size"]);

                                //$inlines = ["ctx._source.categorias.activo=false"];
                                //script del update_by_query con la categoria nueva como parametro
                                $inlines = ["if(ctx._source.categorias !== null) {def targets = ctx._source.categorias.findAll(category -> category.estado == true); for(category in targets) { category.activo = false }}"];

                                $updateCategory["script"] = ["inline" => implode(";", $inlines) ];

                                $updateCategory["indice"] = "catalogo";

                                $anukisUpdateCategoryResponse = $this->elasticUpdate($updateCategory);

                                if ($anukisUpdateCategoryResponse["success"])
                                {
                                        $anukisResponseData = json_decode($anukisUpdateCategoryResponse["data"]->body);
                                        if ($anukisResponseData->updated > 0)
                                        {
                                                $categoryUpdated = true;
                                        }
                                        else
                                        {
                                                $categoryUpdated = false;
                                        }
                                }
                                else
                                {
                                        $categoryUpdated = false;
                                }

                                if ($categoryUpdated)
                                {
                                        $success = true;
                                        $title_response = 'Successful inactive category';
                                        $text_response = 'successful inactive category';
                                        $last_action = 'inactive category';
                                        $data = [];
                                }
                                else
                                {
                                        $success = false;
                                        $title_response = 'Error inactive category';
                                        $text_response = 'Error inactive category, category not found';
                                        $last_action = 'inactive sell';
                                        $data = [
                                            "totalErrors"=> 1,
                                            "errors"=> [
                                                [
                                                    "codError"=> 500,
                                                    "errorMessage"=> "Error inactive category, category not found"
                                                ]
                                            ]
                                        ];
                                }
                        }
                        else
                        {
                                $success = false;
                                $title_response = 'Error inactive category';
                                $text_response = 'Error inactive category, category not found';
                                $last_action = 'inactive sell';
                                $data = [
                                    "totalErrors"=> 1,
                                    "errors"=> [
                                        [
                                            "codError"=> 500,
                                            "errorMessage"=> "Error inactive category, category not found"
                                        ]
                                    ]
                                ];
                        }

                }
                catch(\Exception $exception)
                {
                        $success = false;
                        $title_response = 'Error';
                        $text_response = "Error inesperado al consultar los cobros con los parametros datos";
                        $last_action = 'fetch data from database';
                        $error = $this->getErrorCheckout('E0100');
                        $validate = new Validate();
                        $validate->setError($error->error_code, $error->error_message);
                        $data = array(
                                'totalErrors' => $validate->totalerrors,
                                'errors' => $validate->errorMessage
                        );
                }

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;

        }

        // Colocar productos de esa las categorias en inactivo
        
        private function inactiveProducts($clientId)
        {

                try
                {

                        $search = new Search();
                        $search->addQuery(new MatchQuery('cliente_id', $clientId) , BoolQuery::FILTER);
                        $updateData = $search->toArray();

                        $inlines = ["ctx._source.activo=false"];

                        $updateData["script"] = ["inline" => implode(";", $inlines) ];
                        $updateData["indice"] = "producto";

                        $anukisUpdateCategoryResponse = $this->elasticUpdate($updateData);

                        if ($anukisUpdateCategoryResponse["success"])
                        {
                                $anukisResponseData = json_decode($anukisUpdateCategoryResponse["data"]->body);
                                if ($anukisResponseData->updated > 0)
                                {
                                        $categoryUpdated = true;
                                }
                                else
                                {
                                        $categoryUpdated = false;
                                }
                        }
                        else
                        {
                                $categoryUpdated = false;
                        }

                        if ($categoryUpdated)
                        {
                                $success = true;
                                $title_response = 'Successful inactive product';
                                $text_response = 'successful inactive product';
                                $last_action = 'inactive product';
                                $data = [];
                        }
                        else
                        {
                                $success = false;
                                $title_response = 'Error inactive product';
                                $text_response = 'Error inactive product, product not found';
                                $last_action = 'inactive sell';
                                $data = [
                                    "totalErrors"=> 1,
                                    "errors"=> [
                                        [
                                            "codError"=> 500,
                                            "errorMessage"=> "Error inactive product, product not foundd"
                                        ]
                                    ]
                                ];

                        }

                }
                catch(\Exception $exception)
                {
                        $success = false;
                        $title_response = 'Error';
                        $text_response = "Error inesperado al consultar los cobros con los parametros datos";
                        $last_action = 'fetch data from database';
                        $error = $this->getErrorCheckout('E0100');
                        $validate = new Validate();
                        $validate->setError($error->error_code, $error->error_message);
                        $data = array(
                                'totalErrors' => $validate->totalerrors,
                                'errors' => $validate->errorMessage
                        );
                }

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;

        }
}

