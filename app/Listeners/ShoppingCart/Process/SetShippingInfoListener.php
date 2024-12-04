<?php

namespace App\Listeners\ShoppingCart\Process;

use \Illuminate\Http\Request;
use App\Events\ShoppingCart\Process\SetShippingInfoEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Messages\CommonText as CT;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Aggregation\Metric\SumAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;

use App\Models\BblClientesInfoPagoEnvio;
use App\Models\BblDiscountCode;
use Exception;

class SetShippingInfoListener extends HelperPago
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

    public function handle(SetShippingInfoEvent $event)
    {
        try {
            $fieldValidation    = $event->arr_parametros;
            $clientId           = $fieldValidation["clientId"];
            $id                 = $fieldValidation["id"];
            $name               = $fieldValidation["name"];
            $lastName           = $fieldValidation["lastName"];
            $address            = $fieldValidation["address"];
            $property           = $fieldValidation["property"];
            $conditions         = isset($fieldValidation["conditions"])?$fieldValidation["conditions"]:false;
            $terms              = isset($fieldValidation["terms"])?$fieldValidation["terms"]:false;
            $phone              = $fieldValidation["phone"];
            $city               = isset($fieldValidation["city"])?$fieldValidation["city"]:"";
            $shippingAmount     = $fieldValidation["shippingAmount"];
            $landingIdentifier  = $fieldValidation["landingIdentifier"];
            $contactName        = $fieldValidation["contactName"];
            $contactPhone       = $fieldValidation["contactPhone"];
            $documentType       = $this->getFieldValidation((array)$fieldValidation,"documentType","");
            $documentNumber     = $this->getFieldValidation((array)$fieldValidation,"documentNumber","");
            $email              = $fieldValidation["email"];
            $franchise          = $this->getFieldValidation((array)$fieldValidation,"franchise","");
            $ip                 = $fieldValidation["ip"];
            $quote              = $this->getFieldValidation((array)$fieldValidation,CT::QUOTE_EN,null);
            $codeDane           = $this->getFieldValidation((array)$fieldValidation,CT::CODEDANE_EN,"");
            $country            = $fieldValidation["country"];
            $region             = $fieldValidation["region"];
            $other              = $fieldValidation["other"];
            $saveInfoShipping   = $fieldValidation["saveInfoShipping"];
            $discountCodes      = $fieldValidation["discountCodes"];
            $discountAmount     = $fieldValidation["discountAmount"];
            $amount             = $fieldValidation["amount"];
            if(!$conditions || !$terms){
                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "terms and conditions not accepted";
                $arr_respuesta['textResponse'] = "terms and conditions not accepted";
                $arr_respuesta['lastAction'] = "validate terms and conditions";
                $arr_respuesta['data'] = [];

                return $arr_respuesta;
            }

            $shippingAmount = $shippingAmount <= 0 ? 0 : $shippingAmount;

            //Validar que exista el carrito 
            $searchShoppingCart = $this->searchShoppingCart($id,$clientId);
            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);

            if($shoppingCartResult["status"]){
                if(count($shoppingCartResult["data"])>0){
                    
                    $shoppingCart = $shoppingCartResult["data"][0];

                    if($shoppingCart->estado === "activo"){

                        $comission = 0;
                        $dataDiscountCodes = $this->getDataDiscountCodes($discountCodes,$clientId);
                        $credit = ($shoppingCart->total + $shippingAmount) - $comission;

                        $shippingInfo = [
                            "nombre"=>$name." ".$lastName,
                            "direccion"=>$address,
                            "inmueble"=>$property,
                            "ciudad"=>$city,
                            "valor_envio"=>$shippingAmount,
                            "telefono"=>$phone,
                            "terminos_condiciones"=>true,
                            "tipo_document"=>$documentType,
                            "numero_documento"=>$documentNumber,
                            "correo"=>$email,
                            CT::CODEDANE=>$codeDane
                        ];
                        
                        $shoppingCartUpdateScriptSource = [
                            "ctx._source.envio = params.shipping",
                            "ctx._source.estado = params.state",
                            "ctx._source.fecha = params.date",
                            "ctx._source.comision = params.comission",
                            "ctx._source.abono = params.credit",
                            "ctx._source.identificador=params.landingIdentifier",
                            "ctx._source.numero_contacto=params.contactPhone",
                            "ctx._source.nombre_contacto=params.contactName",
                            "ctx._source.canal_pago=params.paymentChannel",
                            "ctx._source.ip=params.ip",
                            "ctx._source.codigos_descuento=params.discountCodes",
                            "ctx._source.total=params.amount",
                            "ctx._source.total_codigo_descuento=params.discountAmount",
                            "ctx._source.".CT::QUOTE."=params.".CT::QUOTE_EN
                        ];

                        $shoppingCartUpdateScript = [
                            "source"=>implode (";", $shoppingCartUpdateScriptSource),
                            "params"=>[
                                "shipping"=>$shippingInfo,
                                "shippingAmount"=>$shippingAmount,
                                "credit"=>$credit,
                                "comission"=>$comission,
                                "landingIdentifier"=>$landingIdentifier,
                                "contactPhone"=>$contactPhone,
                                "contactName"=>$contactName,
                                "state"=>"procesando_pago",
                                "paymentChannel"=>$franchise,
                                "date"=> date("c"),
                                "ip"=>$ip,
                                "discountCodes"=>$dataDiscountCodes,
                                "amount"=>$amount,
                                "discountAmount"=>$discountAmount,
                                CT::QUOTE_EN=>$quote
                            ]
                        ];
                        $updateShoppingCart = $this->getUpdateShoppingCartQuery($searchShoppingCart->toArray(),$shoppingCartUpdateScript);
                        $anukisUpdateshoppingCartResponse = $this->elasticUpdate($updateShoppingCart);
                        if($anukisUpdateshoppingCartResponse["success"]){
                            $anukisResponseData = json_decode($anukisUpdateshoppingCartResponse["data"]->body);
                            if($anukisResponseData->updated > 0){
                                $shoppingCartUpdated = true;
                            }else{
                                $shoppingCartUpdated = false;
                            }
                        }else{
                            $shoppingCartUpdated = false;
                        }

                        if($shoppingCartUpdated){
                            $this->saveInfoShipping($saveInfoShipping,$name,$phone,$city,$address,$country,$region,$other,$contactPhone,$clientId,$shoppingCart->catalogo_id,$documentNumber,$lastName,$email,$codeDane);
                            $success = true;
                            $title_response = 'Set shipping information';
                            $text_response = 'Set shipping information';
                            $last_action = 'set_shoppingcart_shipping_info';
                            $data = [];  
                        }else{
                            $success = false;
                            $title_response = 'Error in set shipping information';
                            $text_response = 'Error in set shipping information';
                            $last_action = 'set_shoppingcart_shipping_info';
                            $data = [];        
                        }
                    }else{
                        $success = false;
                        $title_response = 'Shoppingcart is not active';
                        $text_response = $shoppingCart->estado;
                        $last_action = 'consult_shopping_cart';
                        $data = [];    
                    }
                }else{
                    $success = false;
                    $title_response = 'Shopping cart not found';
                    $text_response = 'Shopping cart not found';
                    $last_action = 'consult_shopping_cart';
                    $data = [];    
                }
            }else{
                $success = false;
                $title_response = 'Unsuccessfully consult shopping cart';
                $text_response = 'Unsuccessfully consult shopping cart';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get shopping cart";
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

    public function searchShoppingCart($id,$clientId){
        $searchShoppingCart = new Search();
        $searchShoppingCart->setSize(1);
        $searchShoppingCart->setFrom(0);
        $searchShoppingCart->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
        $searchShoppingCart->addQuery(new MatchQuery('clienteId', $clientId), BoolQuery::FILTER);
        return $searchShoppingCart;
    }

    public function getUpdateShoppingCartQuery($updateShoppingCart,$script){
        unset($updateShoppingCart["from"]);
        unset($updateShoppingCart["size"]);
        
        $updateShoppingCart["script"] = $script;
        $updateShoppingCart["indice"] = "shoppingcart";  

        return $updateShoppingCart;
    }
    private function getFieldValidation($fields,$name,$default = ""){

        return isset($fields[$name]) ? $fields[$name] : $default;

    }
    private function saveInfoShipping($saveInfoShipping,$name,$phone,$city,$address,$country,$region,$other,$contactPhone,$clientId,$catalogoId,$documentNumber,$lastName,$email,$codeDane){

        if($saveInfoShipping==true){
            $firstName=$name;
            $bblClientesInfoPagoEnvio=BblClientesInfoPagoEnvio::where("catalogo_id",$catalogoId)->where("email",$email)->first();
            if(!$bblClientesInfoPagoEnvio){
                $bblClientesInfoPagoEnvio= new BblClientesInfoPagoEnvio();
            }
            $bblClientesInfoPagoEnvio->bbl_cliente_id=$clientId;
            $bblClientesInfoPagoEnvio->nombre=$firstName;
            $bblClientesInfoPagoEnvio->apellido=$lastName;
            $bblClientesInfoPagoEnvio->telefono=$phone;
            $bblClientesInfoPagoEnvio->pais=$country;
            $bblClientesInfoPagoEnvio->region=$region;
            $bblClientesInfoPagoEnvio->ciudad=$city;
            $bblClientesInfoPagoEnvio->direccion=$address;
            $bblClientesInfoPagoEnvio->telefono_contacto=$contactPhone;
            $bblClientesInfoPagoEnvio->otros=$other;
            $bblClientesInfoPagoEnvio->catalogo_id=$catalogoId;
            $bblClientesInfoPagoEnvio->document_number=$documentNumber;
            $bblClientesInfoPagoEnvio->email=$email;
            $bblClientesInfoPagoEnvio->codeDane=$codeDane;
            $bblClientesInfoPagoEnvio->save();

        }
    }
    private function getDataDiscountCodes($discountCodes,$clientId){
        $outDiscountCodes=[];
            if(count($discountCodes)>0){
                foreach($discountCodes as $code){
                    $bblDiscountCode=BblDiscountCode::where("nombre",$code)->where("cliente_id",$clientId);
                    if($bblDiscountCode->count()>0){
                        $dataCode=$bblDiscountCode->first();
                        $newDatacode['nombre']=$code;
                        $newDatacode['tipo_descuento']=$dataCode['tipo_descuento'];
                        $newDatacode['monto_descuento']=$dataCode['monto_descuento'];
                        $outDiscountCodes[]=$newDatacode;
                    }
                }
                

            }
            return $outDiscountCodes;
            
    }
}
