<?php

namespace App\Listeners\Catalogue\Process;


use App\Helpers\Messages\CommonText as CM;
use App\Listeners\Services\VendeConfigPlanService;
use App\Events\Catalogue\Process\ConsultCatalogueListEvent;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use Illuminate\Http\Request;


use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Aggregation\Metric\SumAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;

class ConsultCatalogueListListener extends HelperPago
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
    public function handle(ConsultCatalogueListEvent $event)
    {

        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $pagination = $fieldValidation["pagination"];

            $id = $this->getFieldValidation((array)$filters,'id');
            $name = $this->getFieldValidation((array)$filters,'name');
            $strictName = $this->getFieldValidation((array)$filters,'strictName');
            $initialDate = $this->getFieldValidation((array)$filters,'initialDate');
            $endDate = $this->getFieldValidation((array)$filters,'endDate');
            $origin = $this->getFieldValidation((array)$filters,"origin");
            $progress = $this->getFieldValidation((array)$filters,"progress");
            $notFound = $this->getFieldValidation((array)$filters,"notFound");
            $searchValue = $this->getFieldValidation((array)$filters,'search');

            $page = $this->getFieldValidation((array)$pagination,'page',1);
            $pageSize = $this->getFieldValidation((array)$pagination,'limit',50);
            $apifyClient = $this->getAlliedEntity($clientId);

            $vendeConfig = new VendeConfigPlanService();
            $origin = "epayco";

            $search = new Search();
            $search->setSize($pageSize);
            $search->setFrom($page-1);
            if ($search->getFrom() > 0) {
                $search->setFrom(($search->getFrom() * $pageSize));
            }

            $search->setSource([
                "nombre",
                "fecha",
                "fecha_actualizacion",
                "imagen",
                "id",
                HelperEdata::EDATA_STATE,
                "nombre_empresa",
                "procede",
                "telefono_contacto",
                "correo_contacto",
                "whatsapp_activo",
                "color",
                "progreso",
                "activo",
                "banners",
                "indicativo_pais",
                "estado_plan",
                "analiticas",
                "moneda",
                "dominio_propio",
                "valor_dominio_propio",
                "valor_subdominio_propio",
                CM::DELETE_OWNDOMAINVALUE,
                CM::DELETE_OWNSUBDOMAINVALUE,
                CM::PROVIDER_DELIVERY,
                CM::EPAYCO_LOGISTIC,
                CM::SENDER_TYPE,
                CM::SENDER_FIRSTNAME,
                CM::SENDER_LASTNAME,
                CM::SENDER_DOC_TYPE,
                CM::SENDER_DOC,
                CM::SENDER_PHONE,
                CM::SENDER_BUSINESS,
                CM::EPAYCO_DELIVERY_PROVIDER_VALUES,
                CM::PICKUP_CITY,
                CM::PICKUP_DEPARTAMENT,
                CM::PICKUP_ADDRESS,
                CM::PICKUP_CONFIGURATION_ID,
                CM::AUTOMATIC_PICKUP,
                CM::FREE_DELIVERY,
            ]);

            if(!$notFound){
                $search->addQuery(new MatchQuery('estado', true), BoolQuery::FILTER);
            }
            $search->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);

            if ($id != "") $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
            if ($strictName && $name != "") {
                $search->addQuery(new MatchQuery('nombre', $name), BoolQuery::MUST);
            } else if ($name != ""){
                $this->addSearchValue($search,$name);
            }

            $this->addSearchValue($search,$searchValue);

            $this->setRangeDateSearch($initialDate,$endDate,$search);
            $this->setEpaycoSearch($origin,$progress,$search);
            $this->filterSortCatalogueEpayco($origin, $search, $strictName);

            $catalogueResult = $this->consultElasticSearch($search->toArray(), "catalogo", false);
            $catalogueList = [];

            foreach ($catalogueResult["data"] as $catalogue) {

                $catalogueId = $this->getFieldValidation((array)$catalogue, 'id', 0);

                $searchSolds = new Search();
                $searchSolds->setSize(500);
                $searchSolds->setFrom(0);
                $searchSolds->addQuery(new MatchQuery('catalogo_id', $catalogueId), BoolQuery::FILTER);
                $searchSolds->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);
                if($origin == "epayco"){
                    $searchSolds->addQuery(new MatchQuery('activo', true), BoolQuery::FILTER);
                }
                $searchSolds->addAggregation(new SumAggregation('disponible', 'disponible'));
                $searchSolds->addAggregation(new SumAggregation('ventas', 'ventas'));

                $soldsResults = $this->consultElasticSearch($searchSolds->toArray(), "producto", false);

                $catalogueSolds = 0;
                $catalogueProductsCount = 0;
                if ($soldsResults["status"]) {
                    $catalogueSolds = $soldsResults["aggregations"]->ventas->value;
                    $catalogueProductsCount = $soldsResults["aggregations"]->disponible->value;
                }

                $catalogueResponseData = [
                    "date" => $this->getDateValidation((array)$catalogue, 'fecha'),
                    "updateDate" => $this->getDateValidation((array)$catalogue, 'fecha_actualizacion'),
                    "name" => $this->getFieldValidation((array)$catalogue, 'nombre'),
                    "image" => (isset($catalogue->imagen) && $catalogue->imagen != "") ? getenv("AWS_BASE_PUBLIC_URL") . '/' . $catalogue->imagen : "",
                    "id" => $catalogueId,
                    "availableproducts" => $catalogueProductsCount,
                    "soldproducts" => $catalogueSolds,
                    "edataStatus" => $this->validateEnabledClient($origin,$catalogue,$clientId)
                ];

                 $this->addStatusCatalogue((array)$catalogue, $origin, $catalogueResponseData);
                 $this->setEpaycoCatalogueResponseData((array)$catalogue,$catalogueResponseData,$origin);

                array_push($catalogueList, $catalogueResponseData);
            }
            $totalCount = $catalogueResult['pagination']['totalCount'];
            $responseData = [
                "data" => $catalogueList,
                "current_page" => $page,
                "from"=> $page<=1?1:($page *$pageSize)-($pageSize-1),
                "last_page"=> ceil($totalCount/$pageSize),
                "next_page_url"=> "/catalogue?page=".($page+1),
                "path"=> $this->getClientSubdomain($clientId, $origin),
                "per_page"=> $pageSize,
                "prev_page_url"=> $page<=1?null:"/catalogue?pague=".($page-1),
                "to"=> $page<=1?count($catalogueList): ($page *$pageSize)-($pageSize-1)+(count($catalogueList)-1),
                "total"=> $totalCount
            ];

            $success = true;
            $title_response = 'Successfully consult list catalogue';
            $text_response = 'Successfully consult list catalogue';
            $last_action = 'consult_list_catalogue';
            $data = $responseData;
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error '.$exception->getMessage();
            $text_response = "Error query to database";
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

    private function validateEnabledClient($origin,$catalogue,$clientId){
        $edataStatus = $this->getFieldValidation((array)$catalogue, 'edata_estado', HelperEdata::STATUS_ALLOW);

        return $edataStatus;
    }

    private function addStatusCatalogue($catalogue, $origin, &$catalogueResponseData){
         if($origin === 'epayco') {
             $isActive = isset($catalogue['activo']) ? $catalogue['activo'] : true;
             $status = 'en construcción';
             $progressCatalogue = $this->getFieldValidation($catalogue,'progreso',$status);

             if ($isActive && $progressCatalogue === "publicado") {
                 $status = "activo";
             } else if (!$isActive) {
                 $status = "inactivo";
             }


             if($catalogue[HelperEdata::EDATA_STATE] && $catalogue[HelperEdata::EDATA_STATE] == HelperEdata::STATUS_ALERT){
                 $status = HelperEdata::STATUS_ALERT;
             }

             if (isset($catalogue['estado_plan']) && $catalogue['estado_plan'] === 'suspendido') {
                $status = "suspendido";
             }

             $catalogueResponseData['statusCatalogue'] = $status;
         }
    }

    private function filterSortCatalogueEpayco($origin, $search, $strictName = false){
        if($origin === 'epayco') {
            if ($strictName) {
                $search->addSort(new FieldSort('_score', 'DESC'));
            }
            $search->addSort(new FieldSort('fecha', 'DESC'));
        }
    }

    private function addSearchValue(&$search,$searchValue){
        if($searchValue!=""){
            $arraySearch = ['nombre','id'];

            if(intval($searchValue)==0){
                unset($arraySearch[1]);
            }

            $queryStringQuery = new QueryStringQuery('(*'.$searchValue.'*) OR ('.$searchValue.')');
            $queryStringQuery->addParameter('fields', $arraySearch);
            $queryStringQuery->addParameter('analyze_wildcard', true);
            $search->addQuery($queryStringQuery);
        }
    }


    private function getClientSubdomain($clientId, $origin){
        $clientSubdomainSearch =  BblClientes::find($clientId);

        $path = '';
        isset($clientSubdomainSearch->url) && $path = $clientSubdomainSearch->url;

        $origin !== 'epayco' ? $path = $path."/catalogo/" : $path = $path."/vende/";

        return $path;
    }

    private function getFieldValidation($fields,$name,$default = ""){

        return isset($fields[$name]) ? $fields[$name] : $default;

    }

    private function getDateValidation($fields,$name,$default=""){

        return  isset($fields[$name]) ? date("Y-m-d H:i:s", strtotime($fields[$name])) : $default;

    }

    private function setRangeDateSearch($initialDate,$endDate,&$search){
        if ($initialDate != "" && $endDate) {
            $rangeQuery = new RangeQuery('fecha', [
                "gte" => $initialDate,
                "lte" => $endDate
            ]);
            $search->addQuery($rangeQuery, BoolQuery::FILTER);
        } else if ($initialDate != "") {
            $rangeQuery = new RangeQuery('fecha', ["gte" => $initialDate]);
            $search->addQuery($rangeQuery, BoolQuery::FILTER);
        } else if ($endDate != "") {
            $rangeQuery = new RangeQuery('fecha', ["lte" => $endDate]);
            $search->addQuery($rangeQuery, BoolQuery::FILTER);
        }
    }

    private function setEpaycoSearch($origin,$progress,&$search){

        if($origin == "epayco"){

            $search->addQuery(new MatchQuery('procede', $origin), BoolQuery::FILTER);
            if($progress == "building"){
                $bool = new BoolQuery();
                $bool->add(new TermQuery("progreso", "procesando"), BoolQuery::SHOULD);
                $bool->add(new TermQuery("progreso", "completado"), BoolQuery::SHOULD);
                $search->addQuery($bool, BoolQuery::FILTER);
            }else{
                if($progress=="processing"){
                    $search->addQuery(new MatchQuery('progreso', 'procesando'), BoolQuery::FILTER);
                }else if($progress=="completed"){
                    $search->addQuery(new MatchQuery('progreso', 'completado'), BoolQuery::FILTER);
                }else if($progress=="published"){
                    $search->addQuery(new MatchQuery('progreso', 'publicado'), BoolQuery::FILTER);
                }
            }
        }
    }

    private function setEpaycoCatalogueResponseData($catalogue,&$catalogueResponseData,$origin){

        if($origin == "epayco"){
            $catalogueResponseData["companyName"] = $this->getFieldValidation($catalogue,"nombre_empresa");
            $catalogueResponseData["origin"] = $this->getFieldValidation($catalogue,"procede");
            $catalogueResponseData["contactPhone"] = $this->getFieldValidation($catalogue,"telefono_contacto");
            $catalogueResponseData["contactEmail"] = $this->getFieldValidation($catalogue,"correo_contacto");
            $catalogueResponseData["whatsappActive"] = $this->getFieldValidation($catalogue,"whatsapp_activo",false);
            $catalogueResponseData["color"] = $this->getFieldValidation($catalogue,"color");
            $catalogueResponseData["progress"] = $this->getFieldValidation($catalogue,"progreso");
            $catalogueResponseData["banners"] = $this->getBannersUrl($catalogue);
            $catalogueResponseData["active"] = $this->getCatalogueIsActive($catalogue);
            $catalogueResponseData["indicativoPais"] = $this->getFieldValidation($catalogue,"indicativo_pais","+57");
            $catalogueResponseData[CM::CURRENCY_ENG] = $this->getFieldValidation($catalogue,CM::CURRENCY,CM::COP_CURRENCY_CODE);
            $catalogueResponseData["providerDelivery"] = $this->getFieldValidation($catalogue,CM::PROVIDER_DELIVERY);
            $catalogueResponseData["epaycoDeliveryProvider"] = $this->getFieldValidation($catalogue,CM::EPAYCO_LOGISTIC);
            $catalogueResponseData["senderType"] = $this->getFieldValidation($catalogue,CM::SENDER_TYPE);
            $catalogueResponseData["senderFirstname"] = $this->getFieldValidation($catalogue,CM::SENDER_FIRSTNAME);
            $catalogueResponseData["senderLastname"] = $this->getFieldValidation($catalogue,CM::SENDER_LASTNAME);
            $catalogueResponseData["senderDocType"] = $this->getFieldValidation($catalogue,CM::SENDER_DOC_TYPE);
            $catalogueResponseData["senderDoc"] = $this->getFieldValidation($catalogue,CM::SENDER_DOC);
            $catalogueResponseData["senderPhone"] = $this->getFieldValidation($catalogue,CM::SENDER_PHONE);
            $catalogueResponseData["senderBusiness"] = $this->getFieldValidation($catalogue,CM::SENDER_BUSINESS);
            $catalogueResponseData["epaycoDeliveryProviderValues"] = $this->getFieldValidation($catalogue,CM::EPAYCO_DELIVERY_PROVIDER_VALUES);
            $catalogueResponseData["pickupCity"] =  $this->getFieldValidation($catalogue, CM::PICKUP_CITY);
            $catalogueResponseData["pickupDepartament"] = $this->getFieldValidation($catalogue, CM::PICKUP_DEPARTAMENT);
            $catalogueResponseData["pickupAddress"] = $this->getFieldValidation($catalogue, CM::PICKUP_ADDRESS);
            $catalogueResponseData["pickupConfigurationId"] = $this->getFieldValidation($catalogue, CM::PICKUP_CONFIGURATION_ID);
            $catalogueResponseData["automaticPickup"] = $this->getFieldValidation($catalogue, CM::AUTOMATIC_PICKUP);
            $catalogueResponseData["freeDelivery"] = $this->getFieldValidation($catalogue, CM::FREE_DELIVERY);

            //SUBDOMAIN
            $catalogueResponseData["ownDomain"] = $this->getFieldValidation($catalogue,"dominio_propio");
            $catalogueResponseData["ownDomainValue"] = $this->getFieldValidation($catalogue,"valor_dominio_propio");
            $catalogueResponseData["ownSubDomainValue"] = $this->getFieldValidation($catalogue,"valor_subdominio_propio");
            //Si deleteOwnDomainValue es true el dominio se encuentra inactivo
            $catalogueResponseData["deleteOwnDomainValue"] = $this->getFieldValidation($catalogue,CM::DELETE_OWNDOMAINVALUE,$this->valueDefaultDomain($catalogueResponseData["ownDomainValue"]));
             //Si deleteOwnSubDomainValue es true el subDominio se encuentra inactivo
            $catalogueResponseData["deleteOwnSubDomainValue"] = $this->getFieldValidation($catalogue,CM::DELETE_OWNSUBDOMAINVALUE,$this->valueDefaultDomain($catalogueResponseData["ownSubDomainValue"]));

            $vendeConfigPlan = new VendeConfigPlanService();
            $totalProductActive = $vendeConfigPlan->getTotalActiveProducts([(object)$catalogue],CM::ORIGIN_EPAYCO);
            $catalogueResponseData["totalProductActive"] = count($totalProductActive);
            $catalogueResponseData["analytics"] = $this->getAnalyticsResponseParams($this->getFieldValidation($catalogue,"analiticas"));


        }
    }
    private function valueDefaultDomain($domain){
        if($domain=="")
            return true;
        else
            return false;

    }
    private function getAnalyticsResponseParams($analytics){

        return [
            "facebookPixelActive"=>$this->getFieldValidation((array)$analytics,"facebook_pixel_active",false),
            "facebookPixelId"=>$this->getFieldValidation((array)$analytics,"facebook_pixel_id",""),
            "googleAnalyticsActive"=>$this->getFieldValidation((array)$analytics,"google_analytics_active",false),
            "googleAnalyticsId"=>$this->getFieldValidation((array)$analytics,"google_analytics_id",""),
            "googleTagManagerActive"=>$this->getFieldValidation((array)$analytics,"google_tag_manager_active",false),
            "googleTagManagerId"=>$this->getFieldValidation((array)$analytics,"google_tag_manager_id","")
        ];

    }

    private function getCatalogueIsActive($catalogue){
        $active = !is_bool($this->getFieldValidation($catalogue,"activo"))  || $this->getFieldValidation($catalogue,"activo") === false ? false : true;

        if(isset($catalogue["estado_plan"]) && $catalogue["estado_plan"]=="suspendido"){
            $active = false;
        }

        return $active;
    }

    private function getBannersUrl($catalogue){

        $banners = $this->getFieldValidation($catalogue,"banners",[]);
        $bannersWithUrl = [];

        foreach($banners as $banner){
            $path = "";
            if($banner != ""){
                $path = getenv("AWS_BASE_PUBLIC_URL") . '/' .$banner;
            }
            array_push($bannersWithUrl,$path);
        }

        return $bannersWithUrl;
    }

}
