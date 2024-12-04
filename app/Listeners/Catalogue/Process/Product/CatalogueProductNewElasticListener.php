<?php

namespace App\Listeners\Catalogue\Process\Product;


use App\Helpers\Messages\CommonText;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use ONGR\ElasticsearchDSL\Search;
use App\Helpers\Edata\HelperEdata;

use App\Http\Validation\Validate as Validate;
use App\Events\CatalogueProductNewElasticEvent;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use App\Helpers\Messages\CommonText as CT;
use App\Exceptions\GeneralException;

class CatalogueProductNewElasticListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */

    private  $rq;
    public function __construct(Request $request)
    {

        parent::__construct($request);
        $this->rq = $request;
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueProductNewElasticEvent $event)
    {

        try {
            $fieldValidation = $event->arr_parametros;
            list($clientId,$title,$invoiceNumber,$description,$amount,$currency,$tax,$base,$discountPrice,$onePayment,$quantity,$available,$expirationDate,$urlResponse,$urlConfirmation,$catalogoId,$nombreContacto,$numeroContacto,$sales,$img,$arr_categorias,$id_edata,$edata_estado,$edata_mensaje,$origin,$discountPrice,$outstanding,$discountRate,$active,$activeTax,$activeConsumptionTax,$consumptionTax,$netAmount,$epaycoDeliveryProvider,$epaycoDeliveryProviderValues, $realWeight, $high, $long, $width, $declaredValue)=$this->validationDefined($fieldValidation);
            list($tiposEnviosArray,$tiposEnviosArrayResponse)=$this->validationShipping($fieldValidation);
            list($setupReferences, $productReferences, $showInventory)=$this->validationReferences($fieldValidation);
            
            $this->validatePrices($discountPrice,$amount);

            $search = new Search();
            $search->setSize(5000);
            $search->setFrom(0);
            $search->addQuery(new MatchQuery('id', $catalogoId), BoolQuery::FILTER);
            
            $query = $search->toArray();
            $query[CommonText::INDEX] = "catalogo";
            // consultar los datos del catalogo a elasticsearch
            $catalogueResult = $this->consultElasticSearch($query, "catalogo", false);

            if (!isset($catalogueResult["data"]) || count($catalogueResult["data"]) == 0) {
                $success = false;
                $title_response = 'CatalogueId not found';
                $text_response = 'CatalogueId is null';
                $last_action = 'create new product';
                $data = [];
                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;   
            }

            list($idProd,$txtcodigo)=$this->validationId($fieldValidation);

            //valido que sea o no para actualizar
            $search = new Search();
            $search->setSize(5000);
            $search->setFrom(0);

            $search->addQuery(new MatchQuery('cliente_id',  $clientId), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('id', $idProd), BoolQuery::FILTER);

            $queryUpdate = $search->toArray();
            list($update,$aux,$productEdataStateBefore)=$this->validateUpdate($queryUpdate, CommonText::PRODUCT);

            $catalogueData = $catalogueResult["data"][0];
            $nameCatalogue = $catalogueData->nombre;


            $urltxtcodigo = "https://default.epayco.me/catalogo/{$nameCatalogue}/{$idProd}";

            $url_qr = $this->ternaryHelper($origin != "epayco",getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE.$urltxtcodigo,"");

            //valido imagenes
            list($arrImg)=$this->validateImage($origin,$img,$clientId,$nameCatalogue,$update,$aux);

            //referencias de un producto
            //sumar referencia
            if ($productReferences) {
                list(
                    $sumRef,
                    $productReferencesArray,
                    $productReferencesArrayResponse
                ) = $origin === "epayco" ?
                    $this->mappingRefEpayco(
                        $origin, $idProd, $clientId, $productReferences, $aux, $nameCatalogue, $description,
                        $invoiceNumber, $urlResponse, $expirationDate, $base, $urlConfirmation, $tax, $currency, $url_qr
                    ) : $this->mappingRef(
                        $origin, $idProd, $clientId, $productReferences, $aux, $nameCatalogue, $description,
                        $invoiceNumber, $urlResponse, $expirationDate, $base, $urlConfirmation, $tax, $currency, $url_qr
                    );
            } else {
                $sumRef = 0;
                $productReferencesArray=[];
                $productReferencesArrayResponse=[];
            }
            $setupReferencesArray = $this->mappingSetupReferences($setupReferences, $origin);

            $apifyClient = $this->getAlliedEntity($clientId);
                $newData = [
                    "id"               => $idProd,
                    "cliente_id"       => $clientId,
                    "fecha"            => date("c"),
                    "fecha_actualizacion" => date("c"),
                    "txtcodigo"        => $txtcodigo,
                    "ruta_qr"          => $url_qr,
                    'route_link'       => $urltxtcodigo,
                    "titulo"           => $title,
                    "numerofactura"    => $invoiceNumber,
                    "descripcion"      => $description,
                    "valor"            => $amount,
                    "moneda"           => $currency,
                    "iva"              => $tax,
                    "base_iva"         => $base,
                    "precio_descuento" => $discountPrice,
                    "cobrounico"       => $onePayment,
                    "cantidad"         => $this->ternaryHelper($sumRef > 0,$sumRef,$quantity),
                    "disponible"       => $this->ternaryHelper($sumRef > 0,$sumRef,$available),
                    "estado"           => 1,
                    "fecha_expiracion" => $expirationDate != null ? $expirationDate->getTimestamp() :  null,
                    "url_respuesta"    => $urlResponse,
                    "url_confirmacion" => $urlConfirmation,
                    "tipocobro"        => 1,
                    "catalogo_id"      => $catalogoId,
                    "nombre_contacto"  => $nombreContacto,
                    "numero_contacto"  => $numeroContacto,
                    "ventas"           => $sales,
                    "img"              => (array) $arrImg,
                    "envio"            => (array) $tiposEnviosArray,
                    "categorias"       => (array) $arr_categorias,
                    "referencias"      => (array) $productReferencesArray,
                    CommonText::ENTIDAD_ALIADA => $apifyClient["alliedEntityId"],
                    CommonText::FECHA_CREACION_CLIENTE => $apifyClient["clientCreatedAt"],
                    HelperEdata::EDATA_STATE     => $edata_estado
                ];

                $this->addEpaycoProperties($newData,$origin,[
                    "discountPrice"=>$discountPrice,
                    "showInventory"=>$showInventory,
                    "outstanding"=>$outstanding,
                    "discountRate"=>$discountRate,
                    "active"=>$active,
                    "activeTax"=>$activeTax,
                    "activeConsumptionTax"=>$activeConsumptionTax,
                    "consumptionTax"=>$consumptionTax,
                    "netAmount"=>$netAmount,
                    "epaycoDeliveryProvider"=>$epaycoDeliveryProvider,
                    "epaycoDeliveryProviderValues"=>$epaycoDeliveryProviderValues,
                    "realWeight" => $realWeight,
                    "high" => $high,
                    "long" => $long,
                    "width" => $width,
                    "declaredValue" => $declaredValue,
                    "setupReferences" =>$setupReferencesArray
                ]);

                //para la respuesta en ingles
                $newResponse=[
                    'id'              => $newData['id'],
                    'clientId'        => $newData['cliente_id'],
                    'date'            => $newData['fecha'],
                    'txtCode'         => $newData['txtcodigo'],
                    'routeQr'         => $newData['ruta_qr'],
                    'routeLink'       => $newData['route_link'],
                    'title'           => $newData['titulo'],
                    'invoiceNumber'   => $newData['numerofactura'],
                    'description'     => $newData['descripcion'],
                    'amount'          => $newData['valor'],
                    'currency'        => $newData['moneda'],
                    'tax'             => $newData['iva'],
                    'baseTax'         => $newData['base_iva'],
                    'available'       => $newData['disponible'],
                    'quantity'        => $newData['cantidad'],
                    'state'           => $newData['estado'],
                    'expirationDate'  => $newData['fecha_expiracion'],
                    'urlResponse'     => $newData['url_respuesta'],
                    'urlConfirmation' => $newData['url_confirmacion'],
                    'contactName'     => $newData['nombre_contacto'],
                    'contactNumber'   => $newData['numero_contacto'],
                    'img'             => (array) $arrImg,
                    'shippingTypes'   => $tiposEnviosArrayResponse,
                    'categories'      => $newData['categorias'],
                    'references'      => $productReferencesArrayResponse,
                    "edataStatus"     => $newData['edata_estado']
                ];

                $this->addEpaycoResponseProperties($newResponse,$newData,$origin);
                $this->validateProductExist($origin, $idProd,$update,$title,(array) $arr_categorias,$clientId);
                if ($update) {

                    unset($queryUpdate["from"]);
                    unset($queryUpdate["size"]);

                    $inlineUpdate = "ctx._source.id = params.id; ctx._source.fecha_actualizacion = params.fecha_actualizacion;ctx._source.cliente_id = params.cliente_id; ctx._source.fecha = params.fecha; ctx._source.txtcodigo = params.txtcodigo; ctx._source.ruta_qr = params.ruta_qr; ctx._source.titulo = params.titulo; ctx._source.numerofactura = params.numerofactura; ctx._source.descripcion = params.descripcion; ctx._source.valor = params.valor; ctx._source.moneda = params.moneda; ctx._source.iva = params.iva; ctx._source.base_iva = params.base_iva; ctx._source.precio_descuento = params.precio_descuento; ctx._source.cobrounico = params.cobrounico; ctx._source.cantidad += params.cantidad; ctx._source.disponible = params.disponible; ctx._source.estado = params.estado; ctx._source.fecha_expiracion = params.fecha_expiracion; ctx._source.url_respuesta = params.url_respuesta;ctx._source.url_confirmacion = params.url_confirmacion; ctx._source.tipocobro = params.tipocobro; ctx._source.catalogo_id = params.catalogo_id;ctx._source.nombre_contacto = params.nombre_contacto;ctx._source.numero_contacto = params.numero_contacto;ctx._source.ventas = params.ventas;ctx._source.img = params.img;ctx._source.envio = params.envio;ctx._source.categorias = params.categorias;ctx._source.referencias = params.referencias;ctx._source.edata_estado = params.edata_estado;ctx._source.edata_estado_anterior = params.edata_estado_anterior;";
                    $newData["edata_estado_anterior"]= $this->getEdataStateBefore($productEdataStateBefore,$newData);
                    $this->addInlineUpdateEpaycoParams($inlineUpdate,$origin,$newData);
                    $queryUpdate["script"] = [
                        "inline" => $inlineUpdate,
                        "params" => $newData
                    ];

                    $queryUpdate[CommonText::INDEX] = CommonText::PRODUCT;
                    $anukisUpdateProductoResponse = $this->elasticUpdate($queryUpdate);
                    if ($anukisUpdateProductoResponse["success"]) {
                        $success = true;
                        $title_response = 'Successful update product';
                        $text_response = 'successful update product';
                        $last_action = 'update product';
                        $data = $newResponse;

                        $this->deleteCatalogueRedis($catalogoId);
                    } else {
                        $success = false;
                        $title_response = 'Error update product';
                        $text_response = 'error update product';
                        $last_action = 'update product';
                        $data = [];
                    }
                } else {

                    $this->createProductoCollectionElastic($newData);
                    $success = true;
                    $title_response = 'Successful consult';
                    $text_response = 'successful consult';
                    $last_action = 'successful consult';
                    $data = $newResponse;

                    $this->deleteCatalogueRedis($catalogoId);
                }

                // Actualizar el registro edata con el id que se creo
                if (!empty($id_edata) && $success) {
                    $edataSearch = new Search();
                    $edataSearch->addQuery(new MatchQuery('id', $id_edata), BoolQuery::FILTER);
                    $updateData = $edataSearch->toArray();
                    $inlines = [
                        "ctx._source.objeto.id='{$data["id"]}'",
                    ];
                    $updateData["script"] = [
                        "inline" => implode(";", $inlines)
                    ];
                    $updateData[CommonText::INDEX] = "edata_registro";
                    $this->elasticUpdate($updateData);
                }
            
        }catch (GeneralException $e){
            $success = false;
            $title_response = $e->getMessage();
            $text_response = $e->getMessage();
            $last_action = 'Create new product';
            $data = $e->getData();
        }catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ';
            $text_response = "Error create new product ";
            $last_action = 'fetch data from database ';
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

    private function mappingSetupReferences($setupReferences, $origin) {
        $setupReferencesArray = [];
        if ($origin == "epayco") {
            foreach ($setupReferences as $value) {
                $item["tipo"] = $value["type"] ;
                $item["nombre"] = $value["name"];
                $item["valores"] = $value["values"] ;
                array_push($setupReferencesArray,$item);
            }
        }
        return $setupReferencesArray;
    }

    private function getEdataStateBefore($edataStateBefore,$newData){

        if($edataStateBefore === $newData[HelperEdata::EDATA_STATE]){
            $edataStateBefore = null;
        }

        return $edataStateBefore;
    }
    private function validateProductExist($origin, $id,$update,$name,$category,$clientId){

        $searchCategory = empty($category)?0:$category[0];
        if(!$origin &&
            (getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") && getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") == CommonText::ACTIVE_ENG)) {

            $searchProductExist = new Search();
            $searchProductExist->setSize(1);
            $searchProductExist->setFrom(0);

            $searchProductExist->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);
            $searchProductExist->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $searchProductExist->addQuery(new MatchQuery('titulo.keyword', $name), BoolQuery::FILTER);
            if($searchCategory!=0)$searchProductExist->addQuery(new MatchQuery('categorias', $searchCategory), BoolQuery::FILTER);

            $searchProductExistResult = $this->consultElasticSearch($searchProductExist->toArray(), CommonText::PRODUCT, false);

            if (count($searchProductExistResult["data"]) > 0 ) {
                if($update){
                    $productData = $searchProductExistResult["data"][0];

                    if($productData->id != $id){
                        throw new GeneralException("product already exist",[['codError'=>500,'errorMessage'=>'Product already exist']]);
                    }
                }else{
                    throw new GeneralException("product already exist",[['codError'=>500,'errorMessage'=>'Product already exist']]);
                }
            }
        }
    }

    public function validationDefined($fieldValidation):array{
        $clientId = $fieldValidation["clientId"];
        $title = isset($fieldValidation["title"]) ? $fieldValidation["title"] : null;
        $invoiceNumber = isset($fieldValidation["invoiceNumber"]) ? $fieldValidation["invoiceNumber"] : null;
        $description = isset($fieldValidation["description"]) ? $fieldValidation["description"] : null;
        $amount = isset($fieldValidation["amount"]) ? $fieldValidation["amount"] : 0;
        $currency = isset($fieldValidation["currency"]) ? $fieldValidation["currency"] : null;
        $tax = isset($fieldValidation["tax"]) && $fieldValidation["tax"] !== "" ? $fieldValidation["tax"] : 0;
        $base = isset($fieldValidation["baseTax"]) ? $fieldValidation["baseTax"] : null;
        $onePayment = isset($fieldValidation["onePayment"]) ? $fieldValidation["onePayment"] : 1;
        $quantity = isset($fieldValidation["quantity"]) ? (int)$fieldValidation["quantity"] : null;
        $availableString = "available";
        $available = isset($fieldValidation[$availableString]) ? (int)$fieldValidation[$availableString] : 0;
        $expirationDate = isset($fieldValidation["expirationDate"]) ? $fieldValidation["expirationDate"] : null;
        $urlResponse = isset($fieldValidation["urlResponse"]) ? $fieldValidation["urlResponse"] : null;
        $urlConfirmation = isset($fieldValidation["urlConfirmation"]) ? $fieldValidation["urlConfirmation"] : null;
        $catalogoId = $this->getFieldValidation($fieldValidation,"catalogueId",null);
        $nombreContacto = $this->getFieldValidation($fieldValidation,"contactName",null);
        $numeroContacto = $this->getPhoneNumber($fieldValidation);
        $sales = $this->getFieldValidation($fieldValidation,"sales",0);
        $img = $this->getFieldValidation($fieldValidation,"img",[]);
        $arr_categorias = $this->getFieldValidation($fieldValidation,"categories",[]);
        $id_edata = $this->getFieldValidation($fieldValidation,"id_edata",null);
        $edata_estado = $this->getFieldValidation($fieldValidation,HelperEdata::EDATA_STATE,HelperEdata::STATUS_ALLOW);
        $edata_mensaje =  $this->getFieldValidation($fieldValidation,"edata_mensaje");
        $origin = $this->getFieldValidation($fieldValidation,"origin");
        $discountPrice = $this->getFieldValidation($fieldValidation,"discountPrice",0);
        $discountRate = $this->getFieldValidation($fieldValidation,"discountRate",0);
        $outstanding = $this->getFieldValidation($fieldValidation,"outstanding",false);
        $active = $this->getFieldValidation($fieldValidation,CommonText::ACTIVE_ENG,true);
        $activeTax = $this->getFieldValidation($fieldValidation,"activeTax",false);
        $activeConsumptionTax = $this->getFieldValidation($fieldValidation,"activeConsumptionTax",false);
        $consumptionTax = $this->getFieldValidation($fieldValidation,"consumptionTax",0);
        $netAmount = $this->getFieldValidation($fieldValidation,"netAmount",0);
        $epaycoDeliveryProvider = $this->getFieldValidation($fieldValidation,"epaycoDeliveryProvider",false);
        $epaycoDeliveryProviderValues = $this->getFieldValidation($fieldValidation,"epaycoDeliveryProviderValues",[]);
        $realWeight = $this->getFieldValidation($fieldValidation,"realWeight",0);
        $high = $this->getFieldValidation($fieldValidation,"high",0);
        $long = $this->getFieldValidation($fieldValidation,"long",0);
        $width = $this->getFieldValidation($fieldValidation,"width",0);
        $declaredValue = $this->getFieldValidation($fieldValidation,"declaredValue",0);
        return array($clientId,$title,$invoiceNumber,$description,$amount,$currency,$tax,$base,$discountPrice,$onePayment,$quantity,$available,$expirationDate,$urlResponse,$urlConfirmation,$catalogoId,$nombreContacto,$numeroContacto,$sales,$img,$arr_categorias,$id_edata,$edata_estado,$edata_mensaje,$origin,$discountPrice,$outstanding,$discountRate,$active,$activeTax,$activeConsumptionTax,$consumptionTax,$netAmount,$epaycoDeliveryProvider,$epaycoDeliveryProviderValues,$realWeight,$high,$long,$width,$declaredValue);
    }

    public function getPhoneNumber($fieldValidation){
        $phoneNumber = $this->getFieldValidation($fieldValidation,"contactNumber",null);
        if($phoneNumber && strlen($phoneNumber)==10){
            $phoneNumber = '+57'.$phoneNumber;
        }

        return $phoneNumber;
    }

    public function validationShipping($fieldValidation) : array {
        $tiposEnviosArray = [];
        $tiposEnviosArrayResponse = [];
        if (isset($fieldValidation["shippingTypes"]) && count($fieldValidation["shippingTypes"]) > 0) {                
            foreach ($fieldValidation["shippingTypes"] as $key => $value) {
                $tiposEnviosArray[$key]["tipo"]           = $value["type"] ;
                $tiposEnviosArray[$key]["valor"]          = $value["amount"];
                $tiposEnviosArrayResponse[$key]["type"]   = $value["type"] ;
                $tiposEnviosArrayResponse[$key]["amount"] = $value["amount"];
            }        
        }

        return array($tiposEnviosArray, $tiposEnviosArrayResponse);
    }

    public function validationReferences($fieldValidation) : array {
        $setupReferences = $this->getFieldValidation($fieldValidation,"setupReferences",[]);
        $productReferences = $this->getFieldValidation($fieldValidation,"productReferences",[]);
        $showInventory = $this->getFieldValidation($fieldValidation,"showInventory",false);
        return array($setupReferences, $productReferences, $showInventory);
    }

    public function validationId($fieldValidation): array{
        ///id unico ///
        $timeArray = explode(" ", microtime());
        $timeArray[0] = str_replace('.', '', $timeArray[0]);
        $txtcodigo = str_pad((int) ($timeArray[1] . $timeArray[0]), '5', "0", STR_PAD_LEFT);

        $idProd =  (int) ($timeArray[1] . substr($timeArray[0], 2, 3));

        if (isset($fieldValidation["id"]) && trim($fieldValidation["id"] != "") && $fieldValidation["id"] != null) {
            $idProd =  $fieldValidation["id"];
        } 

        return array($idProd,$txtcodigo);
    }

    public function validateUpdate($queryUpdate,$indice): array  {
         //aux obtener cantidad de la referencia
         $aux = [];
         $productEdataStateBefore = null;
         $invoices = $this->consultElasticSearch($queryUpdate,$indice, false);

         $update = false;
             if(isset($invoices['data'][0]) && isset($invoices['data'][0]->referencias)){                    
                 $update = true;
                 foreach ($invoices['data'][0]->referencias as $key => $value) {
                     $aux[$key]['id']= $value->id;
                     $aux[$key]['cantidad']= $value->cantidad;                     
                 }
             }
             if(isset($invoices['data'][0]) && isset($invoices['data'][0]->img) && count($invoices['data'][0]->img) > 0){
                foreach ($invoices['data'][0]->img as $key => $value) {
                    $aux['img'][$key] = $value;
                }                 
              }
             if(isset($invoices['data'][0])){
                 $productEdataStateBefore = $invoices['data'][0]->edata_estado;
             }
                
             return array ($update, $aux,$productEdataStateBefore);
         
    }

    private function validateImgIsObjectAndImgQuantity($origin,$img){
        
        $maxImg = $origin=="epayco"?5:10;
        if(is_object($img)){
            $img = (array) $img;
        }
        if(count($img)>$maxImg){
            throw new GeneralException(CT::FILES_EXEEDED,[['codError'=>500,'errorMessage'=>CT::FILES_EXEEDED]]);
        }
        return $img;
        
    }

    private function getImgExtension($data){
        $sacarExt = explode('image/', $data[0]);
        if(count($sacarExt)>1){
            $sacarExt = explode(';', $sacarExt[1]);
            if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                throw new GeneralException(CT::FORMAT_NOT_ALLOWED,[['codError'=>500,'errorMessage'=>CT::FORMAT_NOT_ALLOWED]]);
            }
        }else{
            throw new GeneralException("Invalid base 64",[['codError'=>500,'errorMessage'=>'Invalid base 64']]);
        }
        
        return $sacarExt;
    }

    private function updateImagesPathEpayco(&$arrImg,$aux){
        foreach($arrImg as $key=>$newImg){
            foreach($aux as $currentImgKey=>$currentImg){
                if($key==$currentImgKey){
                    if($newImg == "delete"){
                        $arrImg[$key] = "";
                    }else if($newImg == "" || (strpos($newImg, 'https')===0)){
                        $arrImg[$key] = $this->validatePath($newImg);
                    } else {
                        $arrImg[$key] = $newImg;
                    }
                }
            }   
        }
    }

    private function validatePath($url) {
        $paths = explode(getenv("AWS_BASE_PUBLIC_URL") . '/', trim($url));
        if (count($paths) > 1){
            return $paths[1];
        }
        return $url;
    }

    private function deleteCatalogueRedis ($catalogueId){
        $redis =  app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_'.$catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_'.$catalogueId);
        }
    }

    public function validateImage($origin,$img,$clientId,$nameCatalogue, $update = false, $aux= []){
        $arrImg = [];
        $deleteImages = [];
        $this->validateImgIsObjectAndImgQuantity($origin,$img);
        for ($k = 0; $k < count($img); $k++) {
            if (!is_array($img[$k]) && strpos(trim($img[$k]),getenv("AWS_BASE_PUBLIC_URL") . '/')=== 0){
                array_push($deleteImages,$img[$k]);
            } else if (is_array($img[$k]) && count($img[$k]) > 1) {
                $img[$k] = $img[$k][1];
            }

            $data = explode(',', $img[$k]);

            if(count($data)>1){
                
                $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                
                $sacarExt = $this->getImgExtension($data);
                
                $base64 = base64_decode($data[1]);

                //Subir los archivos
                $token = random_int(100, 999);
                $fechaActual = new \DateTime('now');
                $nameFile = "{$clientId}_{$fechaActual->getTimestamp()}_{$token}.{$sacarExt[0]}";
                $urlFile = "vende/productos/{$nameFile}";
                
                file_put_contents(
                    $tmpfname . "." . $sacarExt[0],
                    $base64
                );
                $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

                $this->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
                $arrImg[] = $urlFile;
                unlink($tmpfname . "." . $sacarExt[0]);

            }else if($origin=="epayco"){
                $arrImg[] = $data[0];
            }
        }
        
        if($update && isset($aux['img']) && count($aux['img'])>0){
            if($origin=="epayco"){
                $this->updateImagesPathEpayco($arrImg,$aux['img']);
                
            }else{
                $this->updateImagePath($arrImg,$aux['img'],$deleteImages);                
            }
                             
        }
        return $arrImg = array($arrImg);
    }

    private function updateImagePath(&$arrImg,$aux,$deleteImages){
        
        foreach ($aux as  $value) {
            array_push($arrImg, $value);
        }

        if(!empty($deleteImages)){
            foreach($deleteImages as $deleteImg){
                $imgExplode = explode(getenv("AWS_BASE_PUBLIC_URL") . '/',$deleteImg);
                
                if(isset($imgExplode[1])){
                    $unsetIndex = array_search($imgExplode[1],$arrImg);
                    if($unsetIndex!==false){
                        unset($arrImg[$unsetIndex]);
                    }
                }
            }
        }

        $arrImg = array_values($arrImg);
    }

    public function mappingRef(
        $origin, $idProd, $clientId, $productReferences, $aux, $nameCatalogue, $description, $invoiceNumber,
        $urlResponse, $expirationDate, $base, $urlConfirmation, $tax, $currency, $url_qr) : array
    {
        $edata = new HelperEdata($this->rq, $clientId);
        $sumRef = 0;
        $productReferencesArray=[];
        $productReferencesArrayResponse=[];

        $this->validateReferencesParams($productReferences,$origin);
        for ($i = 1; $i <= 10; $i++) {
            $position = "reference" . $i;
            if (isset($productReferences[$position])) {
                ///id unico se deja esa coma y un espacio en blanco pàra gnorar el otro valor que retorna la funcion ///
                list($idProdReferencia, )=$this->validationId($productReferences[$position]);
                
                $txtcodigoRef = str_pad($idProd, '5', "0", STR_PAD_LEFT);
                            
                
                list($quantityUpdate)=$this->validateCalculateQuantityReference(
                    $productReferences,$position,$aux,$idProdReferencia
                );

                $urltxtcodigoRef = "https://default.epayco.me/catalogo/{$nameCatalogue}/{$idProdReferencia}";

                $url_qrRef = "https://secure2.epayco.io/apprest/printqr?txtcodigo=" . $urltxtcodigoRef;

                $this->sendCurlVariables($url_qr, [], "GET", true);

                $arrImgReference = isset($productReferences[$position]["img"]) ?
                    $this->validateImage($origin,$productReferences[$position]["img"],$clientId,$nameCatalogue)[0]
                    : [] ;
                //se valida con edata
                if (!$edata->validarProducto($productReferences[$position]["name"], $description)) {

                    throw new GeneralException(
                        'catalogue products created',[['codError'=>'AED100','errorMessage'=>$edata->getMensaje()]]
                    );

                }

                $productReferencesArray[] = [
                    "descripcion"      => $description,
                    "numerofactura"    => $invoiceNumber,
                    "url_respuesta"    => $urlResponse,
                    "valor"            => isset($productReferences[$position]["amount"]) ?
                        $productReferences[$position]["amount"] :
                        null,
                    "fecha_expiracion" => $expirationDate,
                    "nombre"           => isset($productReferences[$position]["name"]) ?
                        $productReferences[$position]["name"]
                        : null,
                    "base_iva"         => (!$base) ? $base : 0,
                    "fecha"            => isset($productReferences[$position]["fecha"]) ?
                        (new \DateTime($productReferences[$position]["fecha"]))->format("Y-m-d H:i:s")
                        : null,
                    "url_confirmacion" => $urlConfirmation,
                    "route_link"       => $urltxtcodigoRef,
                    "txtcodigo"        => $txtcodigoRef,
                    "iva"              => $tax,
                    "moneda"           => $currency,
                    "id"               => $idProdReferencia,
                    "rutaqr"           => $url_qrRef,
                    "cantidad"         => $quantityUpdate ,
                    "disponible"       => isset($productReferences[$position]["quantity"]) ?
                        $productReferences[$position]["quantity"]
                        : 0,
                    "img"              => $arrImgReference

                ];
                    
                $productReferencesArrayResponse[] = [
                    "description"     => $description,
                    "invoiceNumber"   => $invoiceNumber,
                    "urlResponse"     => $urlResponse,
                    "amount"          => isset($productReferences[$position]["amount"]) ?
                                    $productReferences[$position]["amount"] :
                                    null,
                    "expirationDate"  => $expirationDate,
                    "name"            => isset($productReferences[$position]["name"]) ?
                    $productReferences[$position]["name"]
                                    : null,
                    "baseTax"         => (!$base) ? $base : 0,
                    "date"            => isset($productReferences[$position]["fecha"]) ?
                                    (new \DateTime($productReferences[$position]["fecha"]))->format("Y-m-d H:i:s")
                                    : null,
                    "urlConfirmation" => $urlConfirmation,
                    "routeLink"       => $urltxtcodigoRef,
                    "txtCode"         => $txtcodigoRef,
                    "tax"             => $tax,
                    "currency"        => $currency,
                    "id"              => $idProdReferencia,
                    "routeQr"         => $url_qrRef,
                    "quantity"        => $quantityUpdate,
                    "available"       => isset($productReferences[$position]["quantity"]) ?
                                        $productReferences[$position]["quantity"]
                                        : 0,
                    "img"             => $arrImgReference

                ];

                if (isset($productReferences[$position]["quantity"])) {
                    $sumRef = $sumRef + $productReferences[$position]["quantity"];
                }

            } else {
                break;
            }
        }

        return array($sumRef,$productReferencesArray,$productReferencesArrayResponse);

    }

    public function mappingRefEpayco(
        $origin, $idProd, $clientId, $productReferences, $aux, $nameCatalogue, $description, $invoiceNumber,
        $urlResponse, $expirationDate, $base, $urlConfirmation, $tax, $currency, $url_qr) : array
    {
        $edata = new HelperEdata($this->rq, $clientId);
        $sumRef = 0;
        $productReferencesArray=[];
        $productReferencesArrayResponse=[];

        $this->validateReferencesParams($productReferences, $origin);
        $maxRef = 50;
        for ($i = 1; $i <= $maxRef; $i++) {
            $position = $i -1;
            if (isset($productReferences[$position])) {
                ///id unico se deja esa coma y un espacio en blanco pàra gnorar el otro valor que retorna la funcion ///
                list($idProdReferencia)=$this->validationId($productReferences[$position]);
                
                $txtcodigoRef = str_pad($idProd, '5', "0", STR_PAD_LEFT);
                            
                
                list($quantityUpdate)=$this->validateCalculateQuantityReference(
                    $productReferences,$position,$aux,$idProdReferencia, $origin
                );

                $arrImgReference = $this->validateReferencesHelper(
                    $origin,$productReferences,$position,$clientId,$nameCatalogue,$description,$url_qr,$edata
                );

                $productReferencesArray[] = [
                    "descripcion"      => $description,
                    "numerofactura"    => $invoiceNumber,
                    "url_respuesta"    => $urlResponse,
                    "valor"            => $this->ternaryHelper(
                        isset($productReferences[$position]["amount"]),$productReferences[$position]["amount"],null
                    ),
                    "fecha_expiracion" => $expirationDate,
                    "nombre"           => $this->ternaryHelper(
                        isset($productReferences[$position]["name"]), $productReferences[$position]["name"],null
                    ),
                    "base_iva"         => (!$base) ? $base : 0,
                    "fecha"            => isset($productReferences[$position]["fecha"]) ?
                        (new \DateTime($productReferences[$position]["fecha"]))->format("Y-m-d H:i:s")
                        : null,
                    "url_confirmacion" => $urlConfirmation,
                    "route_link"       => "",
                    "txtcodigo"        => $txtcodigoRef,
                    "iva"              => $tax,
                    "moneda"           => $currency,
                    "id"               => $idProdReferencia,
                    "rutaqr"           => "",
                    "cantidad"         => (int)$quantityUpdate,
                    "disponible"       => (int)$this->ternaryHelper(
                        isset($productReferences[$position]["quantity"]),$productReferences[$position]["quantity"],0
                    ),
                    "img"              => count($arrImgReference) > 0 ? $arrImgReference[0] : ""


                ];
                $this->addEpaycoProperties(
                    $productReferencesArray[$position], $origin, $productReferences[$position], true
                );


                foreach ($productReferencesArray as $key => $value) {
                    $productReferencesArrayResponse[] = [
                        "description"     =>  $value['descripcion'],
                        "invoiceNumber"   =>  $value['numerofactura'],
                        "urlResponse"     =>  $value['url_respuesta'],
                        "amount"          =>  $value['valor'],
                        "expirationDate"  =>  $value['fecha_expiracion'],
                        "name"            =>  $value['nombre'],
                        "baseTax"         =>  $value['base_iva'],
                        "date"            =>  $value['fecha'],
                        "urlConfirmation" =>  $value['url_confirmacion'],
                        "routeLink"       =>  $value['route_link'],
                        "txtCode"         =>  $value['txtcodigo'],
                        "tax"             =>  $value['iva'],
                        "currency"        =>  $value['moneda'],
                        "id"              =>  $value['id'],
                        "routeQr"         =>  $value['rutaqr'],
                        "quantity"        =>  $value['cantidad'],
                        "available"       =>  $value['disponible'],
                        "img"             =>  $value['img']

                    ];

                    $this->addEpaycoResponseProperties($productReferencesArrayResponse[$key],$value,$origin, true);

                }

                if (isset($productReferences[$position]["quantity"])) {
                    $sumRef = $sumRef + $productReferences[$position]["quantity"];
                }

            } else {
                break;
            }
        }
        return array($sumRef,$productReferencesArray,$productReferencesArrayResponse);

    }

    public function validateReferencesHelper($origin,$productReferences,$position,$clientId,$nameCatalogue,$description,$url_qr,$edata) {

        if ($origin != "epayco") {
            $this->sendCurlVariables($url_qr, [], "GET", true);
        }
        if ($origin != "epayco") {
            $arrImgReference = isset($productReferences[$position]["img"]) ? $this->validateImage($origin,$productReferences[$position]["img"],$clientId,$nameCatalogue)[0] : [];
        } else {
            $arrImgReference = isset($productReferences[$position]["img"]) ? $this->validateImage($origin,[$productReferences[$position]["img"]],$clientId,$nameCatalogue)[0] : [];
            $arrImgReference = $this->ternaryHelper(empty($arrImgReference),[""],$arrImgReference);
        }

        //se valida con edata
        if (!$edata->validarProducto($productReferences[$position]["name"], $description)) {

            throw new GeneralException('catalogue products created',[['codError'=>'AED100','errorMessage'=>$edata->getMensaje()]]);

        }
        return $arrImgReference;
    }

    private function ternaryHelper($codition, $value, $default) {
        try {
            return $codition ? $value : $default;
        }catch (\Exception $exception) {
            return $default;
        }
    }

    private function validateReferencesParams($productReferences,$origin){
        $maxRef = $origin !== 'epayco' ? 10 : 50;
        for ($i = 1; $i <= $maxRef; $i++) {
            $position = "reference" . $i;
            $invalidPositionText = "Invalid ".$position;
            if (isset($productReferences[$position])) {
                if(isset($productReferences[$position]["amount"]) && isset($productReferences[$position]["name"])){
                    $name = $productReferences[$position]["name"];
                    $amount =     $productReferences[$position]["amount"];

                    if(strlen($name) < 1 || strlen($name)>50){
                        throw new GeneralException($invalidPositionText." field name invalid",[['codError'=>500,'errorMessage'=>'field name invalid']]);
                    }

                    if((float)$amount == 0){
                        throw new GeneralException($invalidPositionText." field amount invalid",[['codError'=>500,'errorMessage'=>'field amount invalid']]);
                    }
                }else{
                    throw new GeneralException($invalidPositionText." field amount or name is required",[['codError'=>500,'errorMessage'=>'field amount or name is required']]);
                }

            }else{
                break;
            }
        }
    }

    public function validateCalculateQuantityReference($productReferences,$position,$aux,$idProdReferencia,$origin = null):array{
        $quantityUpdate = isset($productReferences[$position]["quantity"]) ? $productReferences[$position]["quantity"] : 0;
        if(!empty($aux) && $origin != "epayco"){
                        foreach ($aux as  $value) {
                            if(isset($value['id']) && $idProdReferencia == $value['id']){                                 
                                $quantityUpdate = $quantityUpdate + $value['cantidad'];                                
                            }
                        }
        }
        return array($quantityUpdate);
    }

    private function getFieldValidation($fields,$name,$default = ""){

        return isset($fields[$name]) ? $fields[$name] : $default;

    }

    private function addEpaycoProperties(&$newData,$origin,$epaycoProperties, $isResference = false){

        if($origin=="epayco"){
            if (!$isResference) {
                if($epaycoProperties["discountPrice"] > 0 && ($epaycoProperties["discountRate"]<0 || $epaycoProperties["discountRate"]>100)){
                    throw new GeneralException("Discount rate invalid",[['codError'=>500,'errorMessage'=>'Discount rate invalid']]);
                }
                $newData["configuraciones_referencias"] = $epaycoProperties["setupReferences"];
                $newData["porcentaje_descuento"] = $epaycoProperties["discountRate"];
                $newData["mostrar_inventario"] = $epaycoProperties["showInventory"];
                $newData["origen"] = $origin;
                $newData["destacado"] = $epaycoProperties["outstanding"];
                $newData["activo"] = $this->getProductIsActive($epaycoProperties["active"],$newData[HelperEdata::EDATA_STATE]);
                $newData["iva_activo"] = $epaycoProperties["activeTax"];
                $newData["ipoconsumo_activo"] = $epaycoProperties["activeConsumptionTax"];
                $newData["ipoconsumo"] = $epaycoProperties["consumptionTax"];
                $newData["monto_neto"] = $epaycoProperties["netAmount"];
                $newData[CT::EPAYCO_LOGISTIC] = $epaycoProperties["epaycoDeliveryProvider"];
                $newData[CT::EPAYCO_DELIVERY_PROVIDER_VALUES] = $epaycoProperties["epaycoDeliveryProviderValues"];
                $newData[CT::REAL_WEIGHT] = $epaycoProperties["realWeight"];
                $newData[CT::HIGH] = $epaycoProperties["high"];
                $newData[CT::LONG] = $epaycoProperties["long"];
                $newData[CT::WIDTH] = $epaycoProperties["width"];
                $newData[CT::DECLARED_VALUE] = $epaycoProperties["declaredValue"];
            } else {
                $newData["iva"] = $epaycoProperties["tax"];
                $newData["ipoconsumo"] = $epaycoProperties["consumptionTax"];
                $newData["porcentaje_descuento"] = $epaycoProperties["discountRate"];
                $newData["precio_descuento"] = $epaycoProperties["discountPrice"];
                $newData["monto_neto"] = $epaycoProperties["netAmount"];
            }
        }
    }

    private function getProductIsActive($active,$edataStatus){
        if($edataStatus == HelperEdata::STATUS_ALERT){
            $active = false;
        }
        return $active;
    }

    private function addEpaycoResponseProperties(&$newResponse,&$newData,$origin, $isRef = false){
        if($origin == "epayco"){
            if (!$isRef) {
                $newResponse["discountRate"] = $newData["porcentaje_descuento"];
                $newResponse["discountPrice"] = $newData["precio_descuento"];
                $newResponse["showInventory"] = $newData["mostrar_inventario"];
                $newResponse["setupReferences"] = $newData["configuraciones_referencias"];
                $newResponse["origin"] = $newData["origen"];
                $newResponse["outstanding"] = $newData["destacado"];
                $newResponse["activeConsumptionTax"] = $newData["ipoconsumo_activo"];
                $newResponse["consumptionTax"] = $newData["ipoconsumo"];
                $newResponse["activeTax"] = $newData["iva_activo"];
                $newResponse[CommonText::ACTIVE_ENG] = $this->getProductIsActive($newData["activo"],$newData[HelperEdata::EDATA_STATE]);
                $newResponse["netAmount"] = $newData["monto_neto"];
                $newResponse["epaycoDeliveryProvider"] = $newData[CT::EPAYCO_LOGISTIC];
                $newResponse["epaycoDeliveryProviderValues"] = $newData[CT::EPAYCO_DELIVERY_PROVIDER_VALUES];
                $newResponse["realWeight"] = $newData[CT::REAL_WEIGHT];
                $newResponse["high"] = $newData[CT::HIGH];
                $newResponse["long"] = $newData[CT::LONG];
                $newResponse["width"] = $newData[CT::WIDTH];
                $newResponse["declaredValue"] = $newData[CT::DECLARED_VALUE];
            } else {
                $newResponse["discountRate"] = $newData["porcentaje_descuento"];
                $newResponse["discountPrice"] = $newData["precio_descuento"];
                $newResponse["netAmount"] = $newData["monto_neto"];
                $newResponse["consumptionTax"] = $newData["ipoconsumo"];
                $newResponse["tax"] = $newData["iva"];
            }
        }
    }

    private function addInlineUpdateEpaycoParams(&$inlineUpdate,$origin){
        if($origin=="epayco"){
            $inlines = [
                "ctx._source.configuraciones_referencias = params.configuraciones_referencias",
                "ctx._source.mostrar_inventario = params.mostrar_inventario",
                "ctx._source.porcentaje_descuento = params.porcentaje_descuento",
                "ctx._source.destacado = params.destacado",
                "ctx._source.activo = params.activo",
                "ctx._source.iva_activo = params.iva_activo",
                "ctx._source.ipoconsumo_activo = params.ipoconsumo_activo",
                "ctx._source.ipoconsumo = params.ipoconsumo",
                "ctx._source.monto_neto = params.monto_neto",
                "ctx._source.epayco_logistica = params.epayco_logistica",
                "ctx._source.lista_proveedores = params.lista_proveedores",
                "ctx._source.peso_real = params.peso_real",
                "ctx._source.alto = params.alto",
                "ctx._source.largo = params.largo",
                "ctx._source.ancho = params.ancho",
                "ctx._source.valor_declarado = params.valor_declarado",
            ];
            $inlineUpdate = $inlineUpdate.implode(";", $inlines);
        }
    }

    private function validatePrices($discountPrice,$amount){
        if ($discountPrice > $amount) {
            throw new GeneralException("Discount price must be less an amount",[['codError'=>500,'errorMessage'=>'Discount price must be less an amount']]);
        }
    }
}

