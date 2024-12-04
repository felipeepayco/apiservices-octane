<?php
namespace App\Listeners\Catalogue\Process\Category;


use App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesListEvent;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;

class ConsultCatalogueCategoriesListListener extends HelperPago
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
    public function handle(ConsultCatalogueCategoriesListEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $pagination = $fieldValidation["pagination"];
            $filters = $fieldValidation["filter"];
            $id= $this->getFieldValidation((array)$filters,"id");
            $name=$this->getFieldValidation((array)$filters,"name");
            $catalogueName=$this->getFieldValidation((array)$filters,"catalogueName");
            $catalogueId=$this->getFieldValidation((array)$filters,"catalogueId");
            $onlyWithProducts=$this->getFieldValidation((array)$filters,"onlyWithProducts",false);
            $origin = $this->getFieldValidation((array)$filters,"origin");
            $countProducts = $this->getFieldValidation((array)$filters,"countProducts",false);
            $page = $this->getFieldValidation((array)$pagination,'page',1);
            $pageSize = $this->getFieldValidation((array)$pagination,'limit',50);
            $onlyActive = $this->getFieldValidation((array)$filters,'onlyActive',false);
            $apifyClient = $this->getAlliedEntity($clientId);

            $origin = "epayco";

            $searchCategoryExist = new Search();
            $searchCategoryExist->setSize(5000);
            $searchCategoryExist->setFrom(0);

            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $boolQuery->add(new MatchQuery('estado', true), BoolQuery::FILTER);
            if($catalogueId!==""){
                $boolQuery->add(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);
            }
            if($catalogueName!==""){
                $boolQuery->add(new MatchQuery('nombre', $catalogueName), BoolQuery::FILTER);
            }
            if($origin == CommonText::ORIGIN_EPAYCO){
                $boolQuery->add(new MatchQuery('procede', $origin), BoolQuery::FILTER);
            }
            //preparar nested query
            $boolNestedQuery = new BoolQuery();
            $boolNestedQuery->add(new MatchQuery('categorias.estado', true));
            $boolNestedQuery->add(new RangeQuery('categorias.id',["gte"=>2]));
            if($id!==""){
                $boolNestedQuery->add(new MatchQuery('categorias.id', $id));
            }
            if($name!==""){
                $boolNestedQuery->add(new MatchQuery('categorias.nombre', $name));
            }
            
            $nestedQuery = new NestedQuery(
                'categorias',
                $boolNestedQuery
            );


            $nestedQuery->addParameter('inner_hits', ["_source"=>true,"size"=>100]);
            $boolQuery->add($nestedQuery,BoolQuery::MUST);
            // fin preparar nested query

            $searchCategoryExist->addQuery($boolQuery);

            $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray(), "catalogo", false);

            $catalogueName = "";
            $paginator = $this->buildCategoriesDataResponse(
                $searchCategoryExistResult,
                $onlyWithProducts,
                $origin,
                $catalogueName,
                $countProducts,
                $onlyActive
            );
            
            //Ordenar categorias
            $this->orderData($paginator,$origin);

            //iniciar paginacion manual
            $totalCategories = count($paginator);
            $totalPages = ceil($totalCategories/$pageSize);
            
            $paginationOffset = $page == 1 ? 0: ($pageSize * $page)-$pageSize;
            $paginator = array_slice($paginator,$paginationOffset,$pageSize);
            
            //Consultar subdominio del cliente
            $clientSubdomainSearch =  BblClientes::find($clientId);

            $clientSubdomain = isset($clientSubdomainSearch->url)?$clientSubdomainSearch->url:"";

            $newData = [
                "data"=>$paginator,
                "current_page" => $page,
                "from"=> $page<=1?1:($page *$pageSize)-($pageSize-1),
                "last_page"=> $totalPages,
                "next_page_url"=> "/catalogue/category?page=".($page+1),
                "path"=> $this->getPathByOrigin($origin,$clientSubdomain,$catalogueName),
                "per_page"=> $pageSize,
                "prev_page_url"=> $page<=2?null:"/catalogue/category?pague=".($page-1),
                "to"=> $page<=1?count($paginator): ($page *$pageSize)-($pageSize-1)+(count($paginator)-1),
                "total"=> $totalCategories
            ];


            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error '.$exception->getMessage();
            $text_response = "Error query to database ";
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

    private function getPathByOrigin($origin,$clientSubdomain,$catalogueName){
        $path = $clientSubdomain."/catalogo/".urlencode($catalogueName)."/lista-producto/";
        if($origin == CommonText::ORIGIN_EPAYCO){
            $path = $clientSubdomain."/vende/";
        }

        return $path;
    }

    private function getFieldValidation($fields,$name,$default = ""){
        
        return isset($fields[$name]) && !empty($fields[$name]) ? $fields[$name] : $default;

    }

    private function buildCategoriesDataResponse($searchCategoryExistResult,$onlyWithProducts,$origin,&$catalogueName,$countProducts,$onlyActive){
        $paginator = [];

        foreach($searchCategoryExistResult["data"] as $catalogueResult){

            $catalogueName = $catalogueResult->_source->nombre;
            $updateDate = $this->getFieldValidation((array)$catalogueResult->_source,'fecha_actualizacion',$catalogueResult->_source->fecha);
            $categoriesHits = $catalogueResult->inner_hits->categorias->hits->hits;
            $categoryCatalogueId = $catalogueResult->_source->id;

            $categoriesInCatalogue = $this->getCategoriesInCatalogue($categoriesHits);

            foreach($categoriesHits as $categoryHits){
                $categorySource = $categoryHits->_source;
                $newCategory = [
                    "id"=>$categorySource->id,
                    "name"=>$categorySource->nombre,
                    "date"=>date("Y-m-d H:i:s", strtotime($categorySource->fecha)),
                    "catalogueId"=>$categoryCatalogueId,
                    "edataStatus" => $this->getFieldValidation((array)$categorySource,'edata_estado',HelperEdata::STATUS_ALLOW)
                ];

                $this->setEpaycoCategoryData($categorySource,$origin,$newCategory,$catalogueName,$updateDate,$categoriesInCatalogue);

                if($onlyWithProducts || $countProducts){
                    $searchProductsInCategory = new Search();
                    $searchProductsInCategory->setSize(10);
                    $searchProductsInCategory->setFrom(0);

                    $searchProductsInCategory->addQuery(new MatchQuery('estado', 1), BoolQuery::MUST);
                    $searchProductsInCategory->addQuery(new MatchQuery('categorias', $categorySource->id), BoolQuery::MUST);
                    $this->filterCategiesOriginEpayco($searchProductsInCategory, $origin);

                    $productsInCategoryResult = $this->consultElasticSearch($searchProductsInCategory->toArray(), "producto", false);

                    $this->addProductCount($origin,$countProducts,$productsInCategoryResult["data"],$newCategory);


                    if($this->isValid($origin,$onlyActive,$newCategory,$productsInCategoryResult,$onlyWithProducts)){
                        array_push($paginator,$newCategory);
                    }
                }else{
                    array_push($paginator,$newCategory);
                }
            }
        }

        return $paginator;

    }

    private function isValid($origin,$onlyActive,$category,$productsInCategoryResult,$onlyWithProducts){
        $isValid = true;

        if($origin == CommonText::ORIGIN_EPAYCO && $onlyActive && !$category[CommonText::ACTIVE_ENG]) {
            $isValid = false;
        }

        if( $onlyWithProducts && empty($productsInCategoryResult["data"]) ){
            $isValid = false;
        }
        return $isValid;
    }


    private function addProductCount($origin,$countProducts,$productsInCategory,&$newCategory){
        $productsActiveInCategory = 0;
        foreach ($productsInCategory as $product){
            if(isset($product->activo) && $product->activo ){
                $productsActiveInCategory = $productsActiveInCategory+1;
            }
        }
        if($origin == CommonText::ORIGIN_EPAYCO && $countProducts){
            $newCategory["productsInCategory"] = count($productsInCategory);
            $newCategory["productsActiveInCategory"] = $productsActiveInCategory;
        }
    }

    private function getCategoriesInCatalogue($categories){
        $countCategories = 0;
        foreach($categories as $categoryHits) {
            $categorySource = $categoryHits->_source;
            if($categorySource->estado){
                 $countCategories ++;
            }
        }

        return $countCategories;
    }

    private function filterCategiesOriginEpayco(&$search, $origin) {
        if($origin == CommonText::ORIGIN_EPAYCO){
            $search->addQuery(new MatchQuery('activo', true), BoolQuery::MUST);
            $search->addQuery(new RangeQuery('disponible',["gte"=>1]));
        }
    }

    private function setEpaycoCategoryData($categorySource,$origin,&$data,$catalogueName,$updateDate,$categoriesInCatalogue){

        if($origin == CommonText::ORIGIN_EPAYCO){
            $tempActive = true;
            if(isset($categorySource->activo) && !$categorySource->activo ) {
                $tempActive = false;
            }

            $img = $this->getFieldValidation((array)$categorySource,'img');
            $data["logo"] = $img != "" ? getenv("AWS_BASE_PUBLIC_URL")."/".$img:"";
            $data["catalogueName"] = $catalogueName;
            $data["origin"] = CommonText::ORIGIN_EPAYCO;
            $data[CommonText::ACTIVE_ENG] = $this->getFieldValidation((array)$categorySource,'activo',$tempActive);
            $data["statusCategory"] = $this->getCataegoryStatus($data);
            $data["categoriesInCatalogue"] = $categoriesInCatalogue;
            $data["updateDate"] = date("Y-m-d H:i:s", strtotime($updateDate));
        }
    }

    private function getCataegoryStatus($categoryData){

        $status = $categoryData[CommonText::ACTIVE_ENG]?"Activo":"Inactivo";

        if($categoryData["edataStatus"] == HelperEdata::STATUS_ALERT){
            $status = HelperEdata::STATUS_ALERT;
        }

        return $status;
    }


    private function orderData(&$data,$origin){
        if($origin==CommonText::ORIGIN_EPAYCO) {
            usort($data, function ($item1, $item2) {
                    return $item1['date'] < $item2['date'];
            });
        }else{
            usort($data, function ($item1, $item2) {
                return strtolower($item1['name']) > strtolower($item2['name']);
            });
        }
    }
}

