<?php

namespace App\Listeners\Catalogue\Validation;

use App\Common\ProductClientStateCodes;
use App\Common\PlanSubscriptionStateCodes;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueNewEvent;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use Illuminate\Http\Request;
use App\Helpers\Edata\HelperEdata;
use HelperEdata as GlobalHelperEdata;
use App\Helpers\Messages\CommonText as CM;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;


class ValidationGeneralCatalogueNewListener extends HelperPago
{
    const EMPTY = 'empty';

    /**
     * ValidationGeneralCatalogueNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @param ValidationGeneralCatalogueNewEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationGeneralCatalogueNewEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

          //VALIDATE ownDomain VALUE
          if($data["ownDomain"])
          {
              //SEARCH IF DOMAIN OR SUBDOMAIN EXIST
              $search = new Search();
              $search->setSize(1);
              $search->setFrom(0);
              $query = new MatchPhraseQuery('valor_dominio_propio',$data["ownDomainValue"]);
              $search->addQuery(new TermQuery('valor_subdominio_propio', $data["ownSubDomainValue"]), BoolQuery::FILTER);
              $search->addQuery($query);
              $catalogueResult = $this->consultElasticSearch($search->toArray(), "catalogo", false);
            

              //SET VALIDATION
              if(count($catalogueResult["data"]))
              {
                  $validate->setError(500,"the domain already exists");
  
              }
  
              if($data["ownDomainValue"]=="" || $data["ownSubDomainValue"]=="")
              {
                  $validate->setError(500,"the domain or subdomain cannot be empty");
  
              }
              
            //CHECK IF THE DOMAIN NAME IS VALID
            $domain_value=(string) filter_var($data["ownDomainValue"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

            if($domain_value==="")
            {
                $validate->setError(500,"the domain is invalid");
            }

            //CHECK IF THE TOP LEVEL DOMAIN EXIST IN THE STRING
            /*
            UNCOMMENT IF YOU WANT TO RESTRICT SOME DOMAINS
            $valid_domain=$this->validate_tld($domain_value);

            if($domain_value!="" && !$valid_domain)
            {
                $validate->setError(500,"the domain is invalid");
            }*/

            //VALIDATE SUBDOMAIN
            if (!preg_match("#^[a-zA-Z0-9]+$#", $data["ownSubDomainValue"])) 
            {
                $validate->setError(500,"the subdomain is invalid");
 
            }
  
        }

        $clientId = $this->validateIsSet($data,'clientId',false,'int');
        $name = $this->validateIsSet($data,'name',false,'string');
        $id = $this->validateIsSet($data,'id',false,'int');

        $finish = $this->validateIsSet($data,'finish',false);
    
        $img = $this->validateIsSet($data,'image',null);
        $arr_respuesta['image'] = $img;

        $companyName = $this->validateIsSet($data,'companyName',null);
        $arr_respuesta['companyName'] = $companyName;

        $ownDomain = $this->validateIsSet($data,'ownDomain',false,"bool");
        $arr_respuesta['ownDomain'] = $ownDomain;

        $ownDomainValue = $this->validateIsSet($data,'ownDomainValue',null);
        $arr_respuesta['ownDomainValue'] = $ownDomainValue;

        $ownSubDomainValue = $this->validateIsSet($data,'ownSubDomainValue',null);
        $arr_respuesta['ownSubDomainValue'] = $ownSubDomainValue;

        $contactPhone = $this->validateIsSet($data,'contactPhone',null);
        $arr_respuesta['contactPhone'] = $contactPhone;

        $contactEmail = $this->validateIsSet($data,'contactEmail',null);
        $arr_respuesta['contactEmail'] = $contactEmail;

        $providerDelivery = $this->validateIsSet($data,'providerDelivery',false);
        $arr_respuesta['providerDelivery'] = $providerDelivery;

        $epaycoDeliveryProvider = $this->validateIsSet($data,'epaycoDeliveryProvider',false);
        $arr_respuesta['epaycoDeliveryProvider'] = $epaycoDeliveryProvider;

        $epaycoDeliveryProviderValues = $this->validateIsSet($data,'epaycoDeliveryProviderValues',[]);
        $arr_respuesta['epaycoDeliveryProviderValues'] = $epaycoDeliveryProviderValues;

        $senderType = $this->validateIsSet($data,'senderType',null);
        $arr_respuesta['senderType'] = $senderType;

        $senderLastname = $this->validateIsSet($data,'senderLastname',null);
        $arr_respuesta['senderLastname'] = $senderLastname;

        $senderFirstname = $this->validateIsSet($data,'senderFirstname',null);
        $arr_respuesta['senderFirstname'] = $senderFirstname;

        $senderDocType = $this->validateIsSet($data,'senderDocType', null);
        $arr_respuesta['senderDocType'] = $senderDocType;

        $senderDoc = $this->validateIsSet($data,'senderDoc',null);
        $arr_respuesta['senderDoc'] = $senderDoc;

        $senderPhone = $this->validateIsSet($data,'senderPhone',null);
        $arr_respuesta['senderPhone'] = $senderPhone;

        $senderBusiness = $this->validateIsSet($data,'senderBusiness',null);
        $arr_respuesta['senderBusiness'] = $senderBusiness;

        $pickupCity = $this->validateIsSet($data,'pickupCity',null);
        $arr_respuesta['pickupCity'] = $pickupCity;

        $pickupDepartament = $this->validateIsSet($data,'pickupDepartament',null);
        $arr_respuesta['pickupDepartament'] = $pickupDepartament;

        $pickupAddress = $this->validateIsSet($data,'pickupAddress',null);
        $arr_respuesta['pickupAddress'] = $pickupAddress;

        $pickupConfigurationId = $this->validateIsSet($data,'pickupConfigurationId',null);
        $arr_respuesta['pickupConfigurationId'] = $pickupConfigurationId;

        $automaticPickup = $this->validateIsSet($data,'automaticPickup',false);
        $arr_respuesta['automaticPickup'] = $automaticPickup;

        $freeDelivery = $this->validateIsSet($data,'freeDelivery',false);
        $arr_respuesta['freeDelivery'] = $freeDelivery;

        $whatsappActive = $this->validateIsSet($data,'whatsappActive',null);
        $arr_respuesta['whatsappActive'] = $whatsappActive;

        $origin = $this->validateIsSet($data,'origin',null);
        $arr_respuesta['origin'] = $origin;

        $color = $this->validateIsSet($data,'color',null);
        $arr_respuesta['color'] = $color;

        $banners = $this->validateIsSet($data,'banners',null);
        $arr_respuesta['banners'] = $banners;

        $currency = $this->validateIsSet($data,'currency',null);
        $arr_respuesta['currency'] = $currency;

        $progress = $this->validateIsSet($data,'progress',null);
        $active = $this->validateIsSet($data,'active',false);
        $indicativoPais = $this->validateIsSet($data,'indicativoPais',"");

        //SUBDOMAIN VARIABLES
        $ownDomain = $this->validateIsSet($data,'ownDomain',false);
        $arr_respuesta['ownDomain'] = $ownDomain;

        $ownDomainValue = $this->validateIsSet($data,'ownDomainValue',null,'string');
        $arr_respuesta['ownDomainValue'] = $ownDomainValue;

        $ownSubDomainValue = $this->validateIsSet($data,'ownSubDomainValue',null,'string');
        $arr_respuesta['ownSubDomainValue'] = $ownSubDomainValue;

        $arr_respuesta['progress'] = $progress;
        $arr_respuesta['active'] = $active;
        $arr_respuesta['indicativoPais'] = $indicativoPais;

        $analytics = $this->validateIsSet($data,'analytics',[]);

        if(is_array($analytics)){
            $analytics = isset($data["analytics"])?$data["analytics"]:[];
            $analytics=(object)$analytics;
        }else if(!is_object($analytics)){
            $validate->setError(500,"field analytics is type object");
        }
        

        $this->validateAnalyticsItems($analytics,$validate);


        $arr_respuesta["analytics"]=$analytics;
        

        $this->validateParamFormat($arr_respuesta,$validate,$clientId,'clientId',self::EMPTY);

        $this->validateParamFormat($arr_respuesta,$validate,$name,'name','range',true,[1,50]);
        $this->validateParamFormat($arr_respuesta,$validate,$name,'name',self::EMPTY,true);

        $this->validateParamFormat($arr_respuesta,$validate,$id,'id',self::EMPTY);

        if(isset($origin) && $origin == CM::ORIGIN_EPAYCO && !$finish){

            $this->validateParamFormat($arr_respuesta,$validate,$companyName,'companyName',self::EMPTY);
            $this->validateParamFormat($arr_respuesta,$validate,$contactPhone,'contactPhone','phone',false);
            $this->validateParamFormat($arr_respuesta,$validate,$contactEmail,'contactEmail','email',false);
            $this->validateQuantityBbl($id,$active,$validate,$clientId,$progress);
            $this->validateParamFormat($arr_respuesta,$validate,$providerDelivery,'providerDelivery','boolean',false);
            $this->validateParamFormat($arr_respuesta,$validate,$epaycoDeliveryProvider,'epaycoDeliveryProvider','boolean',false);

            if ($epaycoDeliveryProvider) {
                $this->validateParamFormat($arr_respuesta,$validate,$senderType,'senderType',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$senderDocType,'senderDocType',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$senderDoc,'senderDoc',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$senderPhone,'senderPhone',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$pickupCity,'pickupCity',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$pickupDepartament,'pickupDepartament',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$pickupAddress,'pickupAddress',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$pickupConfigurationId,'pickupConfigurationId',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$automaticPickup,'automaticPickup',"boolean",false);
                $this->validateParamFormat($arr_respuesta,$validate,$freeDelivery,'freeDelivery',"boolean",false);
            }
            if ($epaycoDeliveryProvider && $senderType == "Personal") {
                $this->validateParamFormat($arr_respuesta,$validate,$senderFirstname,'senderFirstname',self::EMPTY,false);
                $this->validateParamFormat($arr_respuesta,$validate,$senderLastname,'senderLastname',self::EMPTY,false);
            } else if ($epaycoDeliveryProvider) {
                $this->validateParamFormat($arr_respuesta,$validate,$senderBusiness,'senderBusiness',self::EMPTY,false);
            }
            if ($epaycoDeliveryProvider && empty($epaycoDeliveryProviderValues)) {
                $validate->setError(500,"field epaycoDeliveryProviderValues you can't be empty");
            }

        } else {
            $arr_respuesta["finish"]=$finish;
        }

        if ($validate->totalerrors > 0) {
            $success        = false;
            $last_action    = 'validation data save';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data = [
                'totalErrors' => $validate->totalerrors,
                'errors'      => $validate->errorMessage,
            ];
            $response = [
                'success'        => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data,
            ];

            $this->saveLog(2,$clientId, '', $response, 'catalogue_new');

            return $response;
        }

        /* Aplicar validaciones para las reglas */
        $vendeConfigService = new VendeConfigPlanService();
        $edata = new HelperEdata($this->request, $clientId);
        $idobjet = isset($arr_respuesta["id"]) ? $arr_respuesta["id"] : null;

        if(!$vendeConfigService->activeCatalogueOrProductAfterEdataAllowed($arr_respuesta,"catalogue")){
            
            if (!$edata->validarCatalogo($name, $idobjet)) {

                $last_action = 'catalogue_created';
                $title_response = 'Error created catalogue';
                $text_response = $edata->getMensaje();

                $data = [
                    'totalErrors' => 1,
                    'errors' => [
                        [
                            'codError'     => 'AED100',
                            'errorMessage' => $text_response,
                        ]
                    ]
                ];
                return [
                    'success'       => false,
                    'titleResponse' => $title_response,
                    'textResponse'  => $text_response,
                    'lastAction'    => $last_action,
                    'data'          => $data,
                ];
            }
        }


        $arr_respuesta['success']       = true;
        $arr_respuesta['id_edata']      = $edata->getIdEdata();
        $arr_respuesta['edata_estado']  = $edata->getEdataEstado();
        $arr_respuesta['edata_mensaje'] = $edata->getMensaje();

        return $arr_respuesta;
    }

    private function validateIsSet($data,$key,$default,$cast=""){

        $content = $default;

        if (isset($data[$key])) {
            if($cast=="int"){
                $content = (int) $data[$key];
            }else if($cast=="string"){
                $content = (string) $data[$key];
            }else if($cast=="bool"){
                $content = (bool) $data[$key];
            }else{
                $content = $data[$key];
            }
        }

        return $content;

    }

    private function validateParamFormat(&$arr_respuesta,$validate,$param,$paramName,$validateType,$required=true,$range=[0,0]){
        if (isset($param)) {
            $vparam = true;

            if($validateType == self::EMPTY){
                $vparam = $validate->ValidateVacio($param, $paramName);
            }else if($validateType == 'phone' && $param!=""){
                $vparam = $validate->ValidatePhone($param);
            }else if($validateType == 'email' && $param!=""){
                $vparam = $validate->ValidateEmail($param,$paramName);
            }else if($validateType == 'range'){
                $vparam = $validate->ValidateStringSize($param,$range[0],$range[1]);
            }

            if (!$vparam) {
                $validate->setError(500, 'field '.$paramName.' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if($required){
                $validate->setError(500, 'field '.$paramName.' required');
            }
        }
    }


    private function validateQuantityBbl($id,$activo,$validate,$clientId,$progress){

        //instancio el servicio
        $vendeConfigPlan = new VendeConfigPlanService();
        $configVende = $vendeConfigPlan->validatePlan($clientId);
        if(!$configVende){
            //el codigo 100002 es para identificar el error del plan no activo ni renovado al el cliente (dashboard)
            return $validate->setError(10002,CM::PLAN_CANCEL);
        }

        $totalAllCatalogs = $vendeConfigPlan->getTotalActiveCatalogs($clientId,CM::ORIGIN_EPAYCO, null);
        $totalCatalogs = $vendeConfigPlan->getTotalActiveCatalogs($clientId,CM::ORIGIN_EPAYCO, null, true,true);
        $oldCatalogue = !$id ? null : $vendeConfigPlan->getTotalActiveCatalogs($clientId, CM::ORIGIN_EPAYCO, $id);
        $totalCatalogs = $totalCatalogs ? count($totalCatalogs) : 0;
        $totalAllCatalogs = $totalAllCatalogs ?  count($totalAllCatalogs) : 0;

        //si el catalogo es nuevo y ya posee el limite de catalogos activos ó
        //si lo que desea es activar un catalogo anterior ya teniendo el limite de catalogos activos

    
        if(
            ($id == 0 &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $configVende['allowedCatalogs'] <= $totalCatalogs &&
                $configVende["planState"] == PlanSubscriptionStateCodes::ACTIVE
            )
            ||
            ($id !== 0 &&
                $activo &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $oldCatalogue &&
                !$oldCatalogue[0]->activo &&
                $totalCatalogs >= $configVende['allowedCatalogs'] &&
                $configVende["planState"] == PlanSubscriptionStateCodes::ACTIVE
            )
            ||
            ($configVende["planState"] == PlanSubscriptionStateCodes::INTEGRATION  &&
                ($progress == "publicado" || ($id == 0 && $totalAllCatalogs >= 1))
            )
        ) {
            //el codigo 100001 es para identificar el error por exceder los limites del plan en el cliente (dashboard)
            $validate->setError(100001,CM::PLAN_EXCEEDED);
        }

    }


    private function validateQuantity($id,$activo,$validate,$clientId,$progress){
        //instancio el servicio
        $vendeConfigPlan = new VendeConfigPlanService();
        $configVende = $vendeConfigPlan->validatePlan($clientId);
        if(!$configVende){
            //el codigo 100002 es para identificar el error del plan no activo ni renovado al el cliente (dashboard)
            return $validate->setError(10002,CM::PLAN_CANCEL);
        }

        $totalAllCatalogs = $vendeConfigPlan->getTotalActiveCatalogs($clientId,CM::ORIGIN_EPAYCO, null);
        $totalCatalogs = $vendeConfigPlan->getTotalActiveCatalogs($clientId,CM::ORIGIN_EPAYCO, null, true,true);
        $oldCatalogue = !$id ? null : $vendeConfigPlan->getTotalActiveCatalogs($clientId, CM::ORIGIN_EPAYCO, $id);
        $totalCatalogs = $totalCatalogs ? count($totalCatalogs) : 0;
        $totalAllCatalogs = $totalAllCatalogs ?  count($totalAllCatalogs) : 0;

        //si el catalogo es nuevo y ya posee el limite de catalogos activos ó
        //si lo que desea es activar un catalogo anterior ya teniendo el limite de catalogos activos
        if(
            ($id == 0 &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $configVende['allowedCatalogs'] <= $totalCatalogs &&
                $configVende["planState"] == ProductClientStateCodes::ACTIVE
            )
            ||
            ($id !== 0 &&
                $activo &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $oldCatalogue &&
                !$oldCatalogue[0]->activo &&
                $totalCatalogs >= $configVende['allowedCatalogs'] &&
                $configVende["planState"] == ProductClientStateCodes::ACTIVE
            )
            ||
            ($configVende["planState"] == ProductClientStateCodes::INTEGRATION  &&
                ($progress == "publicado" || ($id == 0 && $totalAllCatalogs >= 1))
            )
        ) {
            //el codigo 100001 es para identificar el error por exceder los limites del plan en el cliente (dashboard)
            $validate->setError(100001,CM::PLAN_EXCEEDED);
        }
    }

    private function validateAnalyticsItems($analytics,$validate){

        $analytics = (array)$analytics;

        if(!empty($analytics)){

            $paramsMustBeBoolean = [
                "facebookPixelActive",
                "googleAnalyticsActive",
                "googleTagManagerActive"
            ];

            $paramsMustBeString = [
                "facebookPixelId",
                "googleAnalyticsId",
                "googleTagManagerId"
            ];

            $this->validateParamsInAnalytics($analytics,$paramsMustBeBoolean,$validate,"boolean");
            $this->validateParamsInAnalytics($analytics,$paramsMustBeString,$validate,"string");
        }
    }

    private function validateParamsInAnalytics($analytics,$paramsMustBe,$validate,$mustBeType){
        foreach ($paramsMustBe as $paramMustBe){
            if(!isset($analytics[$paramMustBe]) ){
                $validate->setError(500,"field analytics.".$paramMustBe." is required");
            }else{
                if(($mustBeType=="boolean" && !is_bool($analytics[$paramMustBe]) ||
                    ($mustBeType=="string" && !is_string($analytics[$paramMustBe])))){
                    $validate->setError(500,"field analytics.".$paramMustBe." must be ".$mustBeType);
                }
            }
        }
    }

    private function validate_tld($str)
    {
        $arr=[".com"];
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }
}
