<?php
namespace App\Listeners\Catalogue\Process\Category;


use App\Helpers\Messages\CommonText;
use App\Listeners\Services\VendeConfigPlanService;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use App\Helpers\Edata\HelperEdata;
use App\Http\Validation\Validate as Validate;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesUpdateEvent;
use App\Exceptions\GeneralException;

class ConsultCatalogueCategoriesUpdateListener extends HelperPago
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

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultCatalogueCategoriesUpdateEvent $event)
    {

        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $name=$fieldValidation["name"];
            $catalogueId=$fieldValidation["catalogueId"];
            $categoryId=$fieldValidation["id"];
            $id_edata = $this->getFieldValidation($fieldValidation,"id_edata",null);
            $edata_estado = $this->getFieldValidation($fieldValidation,HelperEdata::EDATA_STATE,HelperEdata::STATUS_ALLOW);
            $edata_mensaje = $this->getFieldValidation($fieldValidation,"edata_mensaje");
            $origin = $this->getFieldValidation($fieldValidation,"origin");
            $logo = $this->getFieldValidation($fieldValidation,"logo");
            $active = $this->getFieldValidation($fieldValidation,CommonText::ACTIVE_ENG,true);

            $this->validateCategoryExist($origin,$catalogueId,$name,$categoryId,$clientId);

            $searchCatalogue = new Search();
            $searchCatalogue->setSize(10);
            $searchCatalogue->setFrom(0);

            $searchCatalogue->addQuery(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);
            $searchCatalogue->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
            if($origin == CommonText::ORIGIN_EPAYCO){
                $searchCatalogue->addQuery(new MatchQuery('procede', $origin), BoolQuery::FILTER);
            }

            $catalogueResult = $this->consultElasticSearch($searchCatalogue->toArray(), "catalogo", false);

            if(count($catalogueResult["data"])==0){
                throw new GeneralException("Catalogue not found");
            }
            $catalogueName = $catalogueResult['data'][0]->nombre;

            $searchCategoryExist = new Search();
            $searchCategoryExist->setSize(10);
            $searchCategoryExist->setFrom(0);
            $searchCategoryExist->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::MUST);
            //preparar nested query
            $matchQueryCategoryId = new MatchQuery('categorias.id', $categoryId);
            $matchQueryCategoryState = new MatchQuery('categorias.estado', true);

            $boolQuery = new BoolQuery();
            $boolQuery->add($matchQueryCategoryId);
            $boolQuery->add($matchQueryCategoryState);
            $nestedQuery = new NestedQuery(
                CommonText::CATEGORIES,
                $boolQuery
            );

            $nestedQuery->addParameter('inner_hits', ["_source"=>true]);
            // fin preparar nested query
            $searchCategoryExist->addQuery($nestedQuery);
            $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray(), "catalogo", false);

            if(count($searchCategoryExistResult["data"])==0){
                throw new GeneralException("category no found");
            }

            //Se utiliza inner_hits sobre el filtro a las catagorias(nested) para obtener solo
            //el objeto desde el arreglo de categorias que se va a actualizar
            $category = $searchCategoryExistResult["data"][0]->inner_hits->categorias->hits->hits[0]->_source;
            $categoryImage = $this->getFieldValidation((array)$category,"img");
            // $imgPath = $this->;

            $updateCatalogue = $searchCatalogue->toArray();
            unset($updateCatalogue["from"]);
            unset($updateCatalogue["size"]);

            $imageRoute = $this->uploadAws($logo,$clientId,$name,$categoryImage,$origin);
            //script del update_by_query con la categoria nueva como parametro
            $updateCatalogue[CommonText::SCRIPT] = [
                CommonText::INLINE=>"if(ctx._source.categorias !== null) {def targets = ctx._source.categorias.findAll(category -> category.id == params.id); for(category in targets) { category.nombre = params.nombre; category.edata_estado = params.edata_estado;  category.img = params.img;category.fecha_actualizacion = params.updateDate;category.activo = params.active;category.edata_estado_anterior = params.edata_estado_anterior}}",
                "params"=>[
                    "id"=>(int)$categoryId,
                    "nombre"=>$name,
                    HelperEdata::EDATA_STATE => $edata_estado,
                    "edata_estado_anterior"=>$this->getEdataStateBefore($category,$edata_estado),
                    "img"=>$imageRoute,
                    "updateDate"=>date('c'),
                    CommonText::ACTIVE_ENG=> $this->getCategoryIsActive($active,$edata_estado)
                ]
            ];
            $updateCatalogue["indice"] = "catalogo";

            $anukisUpdateCatalogueResponse = $this->elasticUpdate($updateCatalogue);

            $countEnabledProducts = 0;

            if($anukisUpdateCatalogueResponse["success"]){

                $anukisResponseData = json_decode($anukisUpdateCatalogueResponse["data"]->body);
                if($anukisResponseData->updated > 0){
                        $this->changeStatusProductsInCategory($active, $category,$clientId,$countEnabledProducts);
                }
            }

            $newData = [
                "name"=>$name,
                "id"=>$category->id,
                "catalogueId"=>$catalogueId,
                "date"=>date("Y-m-d H:i:s", strtotime($category->fecha)),
                "edataStatus" => $edata_estado
            ];

            $this->setEpaycoDataResponse($newData,$imageRoute,$origin,$catalogueName,$active,$countEnabledProducts);

            $success= true;
            $title_response = 'Successful category';
            $text_response = 'successful category';
            $last_action = 'successful category';
            $data = $newData;

            $redis =  app('redis')->connection();
            $exist = $redis->exists('vende_catalogue_'.$catalogueId);
            if ($exist) {
                $redis->del('vende_catalogue_'.$catalogueId);
            }

            // Actualizar el registro edata con el id que se creo
            if (!empty($id_edata)) {
                $edataSearch = new Search();
                $edataSearch->addQuery(new MatchQuery('id', $id_edata), BoolQuery::FILTER);
                $updateData = $edataSearch->toArray();
                $inlines = [
                    "ctx._source.objeto.id='{$data["id"]}'",
                ];
                $updateData[CommonText::SCRIPT] = [
                    CommonText::INLINE => implode(";", $inlines)
                ];
                $updateData["indice"] = "edata_registro";
                $this->elasticUpdate($updateData);
            }
        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        } catch (GeneralException $generalException){
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "UpdateCatalogueGeneralException";
            $arr_respuesta['data'] = $generalException->getData(); 
            
            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        if ($edata_estado == HelperEdata::STATUS_ALERT) {
            $arr_respuesta['data']['totalErrors'] = 1;
            $arr_respuesta['data']['errors'] = [
                [
                    'codError' => 'AED100',
                    'errorMessage' => $edata_mensaje,
                ]
            ];
        }

        return $arr_respuesta;
    }

    private function getEdataStateBefore($category,$currentEdataState){

        $edataStateBefore = $this->getFieldValidation((array)$category,HelperEdata::EDATA_STATE,null);

        if($edataStateBefore === $currentEdataState){
            $edataStateBefore = null;
        }

        return $edataStateBefore;
    }

    private function getFieldValidation($fields,$name,$default = ""){
        
        return isset($fields[$name]) ? $fields[$name] : $default;

    }

    public function validateCategoryExist($origin,$catalogueId,$categoryName,$categoryId,$clientId){
        if($origin != CommonText::ORIGIN_EPAYCO &&
            (getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") && getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") == CommonText::ACTIVE_ENG)){

            $searchCategoryExist = new Search();
            $searchCategoryExist->setSize(1);
            $searchCategoryExist->setFrom(0);

            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
            $boolQuery->add(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);

            //preparar nested query
            $boolNestedQuery = new BoolQuery();
            $boolNestedQuery->add(new MatchQuery('categorias.estado', true));
            $boolNestedQuery->add(new RangeQuery('categorias.id',["gte"=>2]));
            $boolNestedQuery->add(new MatchQuery('categorias.nombre.keyword', $categoryName));

            $nestedQuery = new NestedQuery(
                CommonText::CATEGORIES,
                $boolNestedQuery
            );

            $nestedQuery->addParameter('inner_hits', ["_source"=>true,"size"=>100]);
            $boolQuery->add($nestedQuery,BoolQuery::MUST);

            // fin preparar nested query

            $searchCategoryExist->addQuery($boolQuery);

            $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray(), "catalogo", false);

            if(count($searchCategoryExistResult["data"])>0){

                $categoryData = $searchCategoryExistResult["data"][0]->inner_hits->categorias->hits->hits[0]->_source;
                if($categoryData->id != $categoryId){
                    throw new GeneralException("category already exist",[['codError'=>500,'errorMessage'=>'Category alredy exist']]);
                }

            }
        }

    }

    public function uploadAws($logo,$clientId,$categoryName,$categoryLogo,$origin){
        
        $imageRoute = $categoryLogo;

        if($origin == CommonText::ORIGIN_EPAYCO){
            if($logo == "delete"){
                $imageRoute = "";
            }else if($logo != "" && (strpos($logo,"https")!==0) ){
                $data = explode(',', $logo);
                $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                $sacarExt = explode('image/', $data[0]);
                $sacarExt = explode(';', $sacarExt[1]);

                if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                    throw new GeneralException("file format not allowed");
                }

                $base64 = base64_decode($data[1]);
                file_put_contents(
                    $tmpfname . "." . $sacarExt[0],
                    $base64
                );


                $fechaActual = new \DateTime('now');


                //Subir los archivos
                $nameFile = "{$clientId}_{$categoryName}_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                $urlFile = "vende/productos/{$nameFile}";
                $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

                $this->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
                unlink($tmpfname . "." . $sacarExt[0]);
                $imageRoute = $urlFile;
            }
        }
        
        return $imageRoute;
    }

    private function setEpaycoDataResponse(&$data,$imageRoute,$origin,$catalogueName,$active,$countEnabledProducts){

        if($origin == CommonText::ORIGIN_EPAYCO){
            $data["logo"] = $imageRoute != "" ? getenv("AWS_BASE_PUBLIC_URL")."/".$imageRoute:"";
            $data["origin"] = $origin;
            $data["catalogueName"] = $catalogueName;
            $data[CommonText::ACTIVE_ENG] = $this->getCategoryIsActive($active,$data["edataStatus"]);
            $data["countEnabledProducts"] = $countEnabledProducts;
        }
    }


    private function getCategoryIsActive($active,$categoryStatus){

        if($categoryStatus == HelperEdata::STATUS_ALERT){
            $active = false;
        }

        return $active;
    }

    private function changeStatusProductsInCategory($active,$category,$clientId,&$countEnabledProducts){

       if($this->validateIsChangeStatusProductsInCategory($category,$active)){

           $vendeConfigPlanService = new VendeConfigPlanService();
           $clientPlan = $vendeConfigPlanService->getClientActivePlanBbl($clientId);
            if(!is_null($clientPlan)){
                $planConfig = $vendeConfigPlanService->getBblPlanConfig($clientPlan);
                $allowedProducst = $planConfig["allowedProducts"];

                $search = new Search();

                if($active && $allowedProducst!=="ilimitado"){

                    $currentActiveProducts = $vendeConfigPlanService->getTotalProductsByCustomFields([
                        [CommonText::CLIENT_ID, $clientId],
                        [CommonText::ACTIVE, true],
                        ["origen",CommonText::ORIGIN_EPAYCO]
                    ]);

                    $productsInCategory = $vendeConfigPlanService->getTotalProductsByCustomFields([
                        [CommonText::CATEGORIES, $category->id],
                        [CommonText::ACTIVE, false],
                        ["origen",CommonText::ORIGIN_EPAYCO]
                    ]);

                    $countCurrentActiveProducts = count($currentActiveProducts);
                    $countAllowedActiveProducst = $allowedProducst - $countCurrentActiveProducts;
                    $enabledProducts = array_slice($productsInCategory,0,$countAllowedActiveProducst);
                    $enabledProductsId = [];

                    foreach ($enabledProducts as $product) {
                        array_push($enabledProductsId,$product->id);
                    }

                    $search->addQuery(new TermsQuery("id", $enabledProductsId), BoolQuery::FILTER);

                }else{
                    $search->addQuery(new MatchQuery(CommonText::CATEGORIES, $category->id), BoolQuery::FILTER);
                }

                $updateData = $search->toArray();

                $inlines = [
                    "ctx._source.activo=params.activo",
                ];

                $updateData[CommonText::SCRIPT] = [CommonText::INLINE=>implode(";",$inlines), "params"=>[CommonText::ACTIVE=>$active]];
                $updateData["indice"] = "producto";
                $updateResponseData = $this->elasticUpdate($updateData);


                if($active){
                    $updateResponseData = json_decode($updateResponseData["data"]->body);
                    $countEnabledProducts = $updateResponseData->updated;
                }
            }
        }
    }

    private function validateIsChangeStatusProductsInCategory($category,$active){
        return ((!isset($category->activo ) && $active === false) || $category->activo !== $active);
    }

}
