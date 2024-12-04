<?php
namespace App\Listeners\Catalogue\Validation;


use App\Listeners\Services\VendeConfigPlanService;
use App\Models\Catalogo;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Edata\HelperEdata;
use App\Http\Validation\Validate as Validate;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Events\ValidationGeneralCatalogueProductNewElasticEvent;
use App\Helpers\Messages\CommonText as CM;
class ValidationGeneralCatalogueProductNewElasticListener extends HelperPago
{
    const EMPTY = 'empty';
    
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
    public function handle(ValidationGeneralCatalogueProductNewElasticEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;
        $arr_respuesta = [];

        $clientId = $this->validateIsSet($data,'clientId',false,'int');
        $this->validateParamFormat($arr_respuesta,$validate,$clientId,'clientId',self::EMPTY);
        $origin = $this->validateIsSet($data,'origin',"");
        $arr_respuesta['origin'] = $origin;
 
        $catalogueId = $this->validateIsSet($data,'catalogueId',false,'int');
        $this->validateParamFormat($arr_respuesta,$validate,$catalogueId,'catalogueId',self::EMPTY);
        
        $id = $this->validateIsSet($data,'id',false,'int');
        $this->validateParamFormat($arr_respuesta,$validate,$id,'id',self::EMPTY);
        
        $moneda = $this->validateIsSet($data,'currency',false,'int');
        $this->validateParamFormat($arr_respuesta,$validate,$moneda,'currency',self::EMPTY);

        $valor = $this->validateIsSet($data,'amount',false,'float',true);
        $this->validateParamFormat($arr_respuesta,$validate,$valor,'amount',self::EMPTY);
        
        $referencia = $this->validateIsSet($data,'reference',"");
        $arr_respuesta["reference"]=$referencia;

        $cobrounico = 0;
        $arr_respuesta["onePayment"]=$cobrounico;

        $cantidad = $this->validateIsSet($data,'quantity',"");
        $this->validateParamFormat($arr_respuesta,$validate,$cantidad,'quantity',self::EMPTY);
        if($origin != "epayco" && !$validate->mayorZero($cantidad) && trim($id) != "" && $id == 0){
					$validate->setError(500, CM::FIELD.' quantity is invalid');

				}
 
        $disponibles = $cantidad;
        if ($id > 0) {
            $disponibles = $disponibles != "" ? (int)$disponibles : $cantidad;
        }
        $arr_respuesta["available"]=$disponibles;

        $urlConfirmacion = $this->validateIsSet($data,'urlConfirmation',"");
        $arr_respuesta["urlConfirmation"]=$urlConfirmacion;


        $urlRespuesta = $this->validateIsSet($data,'urlResponse',"");
        $arr_respuesta["urlResponse"]=$urlRespuesta;

        $discountRate = $this->validateIsSet($data,'discountRate',0,"float");
        $arr_respuesta["discountRate"]=$discountRate;

        $iva = $this->validateIsSet($data,'tax',0);
        $arr_respuesta["tax"]=$iva;

        $base = $this->validateIsSet($data,'baseTax',0);
        $arr_respuesta["baseTax"]=$base;

        $active = $this->validateIsSet($data,'active',true);
        $arr_respuesta['active'] = $active;

        $activeTax = $this->validateIsSet($data,'activeTax',null);
        $arr_respuesta['activeTax'] = $activeTax;

        $consumptionTax = $this->validateIsSet($data,'consumptionTax',null);
        $arr_respuesta['consumptionTax'] = $consumptionTax;

        $activeConsumptionTax = $this->validateIsSet($data,'activeConsumptionTax',null);
        $arr_respuesta['activeConsumptionTax'] = $activeConsumptionTax;
        
        $titulo = $this->validateIsSet($data,'title',"");
        $this->validateParamFormat($arr_respuesta,$validate,$titulo,'title','range',true,[1,50]);
        $this->validateParamFormat($arr_respuesta,$validate,$titulo,'title',self::EMPTY,true);

        $descripcion = $this->validateIsSet($data,'description',"");
        $this->validateParamFormat($arr_respuesta,$validate,$descripcion,'description','range',false,[0,800]);

        $shippingTypes = $this->validateIsSet($data,'shippingTypes',[]);
        $arr_respuesta['shippingTypes'] = $shippingTypes;
        
        $fechavencimiento = $this->validateIsSet($data,'expirationDate',null);
        $this->validateDateFormat($arr_respuesta,$validate,$fechavencimiento,'expirationDate',false);

        $img = $this->validateIsSet($data,'img',null);
        $arr_respuesta["img"]=$img;

        $contactName = $this->validateIsSet($data,'contactName',"");
        $arr_respuesta["contactName"]=$contactName;

        $contactNumber = $this->validateIsSet($data,'contactNumber',"");
        $arr_respuesta["contactNumber"]=$contactNumber;

        $productReferences = $this->validateIsSet($data,'productReferences',null);

        $setupReferences = $this->validateIsSet($data,'setupReferences',[]);
        $showInventory = $this->validateIsSet($data,'showInventory',false);

       
        $discountPrice = $this->validateIsSet($data,'discountPrice',0,'float');
        $arr_respuesta['discountPrice'] = $discountPrice;
        
        $categories = $this->validateIsSet($data,'categories',null);
        $arr_respuesta['categories']=$categories;

        $outstanding = $this->validateIsSet($data,'outstanding',false);
        $arr_respuesta['outstanding']=$outstanding;

        $epaycoDeliveryProvider = $this->validateIsSet($data,'epaycoDeliveryProvider',false);
        $arr_respuesta['epaycoDeliveryProvider'] = $epaycoDeliveryProvider;

        $epaycoDeliveryProviderValues = $this->validateIsSet($data,'epaycoDeliveryProviderValues',[]);
        $arr_respuesta['epaycoDeliveryProviderValues'] = $epaycoDeliveryProviderValues;
        
        if($origin=="epayco"){
            if ($productReferences !== null && is_array($productReferences) && count($productReferences) > 0) {
                $this->validateProductsReferences($validate, 'product', $productReferences);
                $this->validateProductsReferences($validate, 'setupReferences', $setupReferences);
                $arr_respuesta["productReferences"] = $productReferences;
                $arr_respuesta['setupReferences'] = $setupReferences;
                $arr_respuesta['showInventory'] = $showInventory;
            }
            $netAmount = $this->validateIsSet($data,'netAmount',false,'float',true);
            $this->validateParamFormat($arr_respuesta,$validate,$netAmount,'netAmount',self::EMPTY,false);
            $realWeight = $this->validateIsSet($data,'realWeight',false,'float',false);
            $high = $this->validateIsSet($data,'high',false,'float',false);
            $long = $this->validateIsSet($data,'long',false,'float',false);
            $width = $this->validateIsSet($data,'width',false,'float',false);
            $declaredValue = $this->validateIsSet($data,'declaredValue',false,'float',false);
            $this->validateParamFormat($arr_respuesta,$validate,$realWeight,'realWeight',self::EMPTY,false);
            $this->validateParamFormat($arr_respuesta,$validate,$high,'high',self::EMPTY,false);
            $this->validateParamFormat($arr_respuesta,$validate,$long,'long',self::EMPTY,false);
            $this->validateParamFormat($arr_respuesta,$validate,$width,'width',self::EMPTY,false);
            $this->validateParamFormat($arr_respuesta,$validate,$declaredValue,'declaredValue',self::EMPTY,false);
            $this->validateCategories($validate,$categories);
            $this->validateQuantity($id,$active,$validate,$clientId);
            if ($epaycoDeliveryProvider && empty($epaycoDeliveryProviderValues)) {
                $validate->setError(500,"field epaycoDeliveryProviderValues you can't be empty");
            }
        }else{

            if ($iva != 0) {
                $iva = $base * ($iva / 100);
                $arr_respuesta["baseTax"]=$base;
                $arr_respuesta["tax"]=$iva;
            }

            $this->validateParamFormat($arr_respuesta,$validate,$contactName,'contactName',self::EMPTY);
            $this->validateParamFormat($arr_respuesta,$validate,$contactNumber,'contactNumber',self::EMPTY);
        }
        
        if( $validate->totalerrors > 0 ){
            $success         = false;
            $last_action     = 'validation data of filter';
            $title_response  = 'Error';
            $text_response   = 'Some fields are required, please correct the errors and try again';

            $data            =
                array('totalerrors'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse'   => $title_response,
                'textResponse'    => $text_response,
                'lastAction'      => $last_action,
                'data'            => $data
            );

            $this->saveLog(2,$clientId, '', $response, 'catalogue_product_new');

            return $response;
        }

        /* Aplicar validaciones para las reglas */
        $edata = new HelperEdata($this->request, $clientId);
        $idobjet = isset($arr_respuesta["id"]) ? $arr_respuesta["id"] : null;
        $vendeConfigService = new VendeConfigPlanService();
        if(!$vendeConfigService->activeCatalogueOrProductAfterEdataAllowed($arr_respuesta)) {
            if (!$edata->validarProducto($titulo, $descripcion, $idobjet)) {
                $last_action = 'catalogue products created';
                $title_response = 'Error created catalogue products';
                $text_response = $edata->getMensaje();

                $data = [
                    'totalErrors' => 1,
                    'errors' => [
                        [
                            'codError' => 'AED100',
                            'errorMessage' => $text_response,
                        ]
                    ]
                ];
                return [
                    'success' => false,
                    'titleResponse' => $title_response,
                    'textResponse' => $text_response,
                    'lastAction' => $last_action,
                    'data' => $data,
                ];

            }
        }


        //Nueva categoria
        if (isset($data["category_new"])) {
            $arr_respuesta['category_new']=$data["category_new"];
        }
        

        $arr_respuesta['success']       = true;
        $arr_respuesta['id_edata']      = $edata->getIdEdata();
        $arr_respuesta['edata_estado']  = $edata->getEdataEstado();
        $arr_respuesta['edata_mensaje'] = $edata->getMensaje();
        return $arr_respuesta;
        
    }

    private function validateIsSet($data,$key,$default,$cast="",$noZero = false){

        $content = $default;

        if (isset($data[$key])) {
            if($cast=="int"){
                $content = (int) $data[$key];
            }else if($cast =="float"){
                $content = (float) $data[$key];
                if($noZero && $content == 0){
                    $content = "";
                }
            }else if($cast=="string"){
                $content = (string) $data[$key];
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
                $validate->setError(500, CM::FIELD.' '.$paramName.' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if($required){
                $validate->setError(500, CM::FIELD.' '.$paramName.' required');
            }
        }
    }

    private function validateDateFormat(&$arr_respuesta,$validate,$param,$paramName,$required=true){
        if (isset($param)) {
            $vparam = true;

                try{
                    $param=new \DateTime($param);
                }catch (\Exception $exception){
                    $validate->setError(500, "field expirationDate invalidate date type");
                }

            if (!$vparam) {
                $validate->setError(500, CM::FIELD.' '.$paramName.' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if($required){
                $validate->setError(500, CM::FIELD.' '.$paramName.' required');
            }
        }
    }

    private function validateCategories(&$validate,$categories){
        if(is_null($categories)){
            $validate->setError(500, CM::FIELD.' categories required');
        }
    }



    private function validateQuantity($id,$active,$validate,$clientId){
        //instancio el servicio
        $vendeConfigPlan = new VendeConfigPlanService();
        $configVende = $vendeConfigPlan->validatePlan($clientId);
        if(!$configVende){
            //el codigo 100002 es para identificar el error del plan no activo ni renovado al el cliente (dashboard)
            return $validate->setError(10002,CM::PLAN_CANCEL);
        }

        $catalogs = $vendeConfigPlan->getTotalActiveCatalogs($clientId,CM::ORIGIN_EPAYCO, null, true);
        $catalogs = $catalogs ? $catalogs : [];
        $totalProducts = $vendeConfigPlan->getTotalActiveProducts([],CM::ORIGIN_EPAYCO,null,$clientId);
        $oldProduct = !$id ? null : $vendeConfigPlan->getTotalActiveProducts(array(), CM::ORIGIN_EPAYCO, $id);
        $totalProducts = $totalProducts ? count($totalProducts) : 0;
        //si el producto es nuevo y ya posee el limite de productos activos ó
        //si lo que desea es activar un producto anterior ya teniendo el limite de productos activos
        if(($id == 0 && $configVende['allowedProducts'] != 'ilimitado' && $configVende['allowedProducts'] <= $totalProducts) ||
            ($id !== 0 && $active && $configVende['allowedProducts'] != 'ilimitado' && $oldProduct && !$oldProduct[0]->activo && ($totalProducts >= $configVende['allowedProducts']))
        ) {
            //el codigo 100001 es para identificar el error por exceder los limites del plan en el cliente (dashboard)
            $validate->setError(10001,CM::PLAN_EXCEEDED);
        }
    }

    private function validateProductsReferences($validate, $type, $productReferences) {

        $paramsMustBeNumber = [
            "netAmount",
            "amount",
            "discountPrice",
            "quantity",
            "discountRate"
        ];
        foreach ($productReferences as $item) {
            if ($type === 'product') {
                $this->validateParamsInReferences($item,$paramsMustBeNumber,$validate,"number", $type);
                $this->validateParamsInReferences($item,["name"],$validate,"string", $type);
                $this->validateParamsInReferences($item,["img"],$validate,"string", $type , false);
            } else {
                $this->validateParamsInReferences($item,["name"],$validate,"string", $type);
                $this->validateParamsInReferences($item,["type"],$validate,"string", $type);
                $this->validateParamsInReferences($item,["values"],$validate,"array", $type);
            }
        }

    }

    private function validateParamsInReferences($product,$paramsMustBe,$validate,$mustBeType, $type, $required = true){
        foreach ($paramsMustBe as $paramMustBe){
            if($required && !isset($product[$paramMustBe]) ){
                $validate->setError(500,"field ".$type.".".$paramMustBe." is required");
            }else{
                if($required && (($mustBeType=="number" && !is_numeric($product[$paramMustBe])) || ($mustBeType=="string" && !is_string($product[$paramMustBe])) || ($mustBeType=="array" && !is_array($product[$paramMustBe])))){
                    $validate->setError(500,"field ".$type.".".$paramMustBe." must be ".$mustBeType);
                }
            }
        }
    }
}
