<?php

namespace App\Listeners\Payments\Process;

use App\Common\TransactionStateCodes;
use App\Events\Payments\Process\ProcessChargeEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Http\Lib\Utils;
use App\Models\BblClientes;
use App\Models\BblClientesCard;
use App\Models\BblSuscripcion;
use App\Models\BblSuscripcionCargos;

class ProcessChargeListener extends HelperPago
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
    public function handle(ProcessChargeEvent $event)
    {
        $test=false;
        $util = new Utils();
        try {
            $fieldValidation = $event->arr_parametros;  
            $clienteId=$fieldValidation["clientId"];
            $idPlan = $fieldValidation["id_plan"];

            $ip =$util->getRealIP();

           //Falta el id de la suscripcion
            //consultar datos faltantes
            $BblSuscripcion=BblClientes::select("cliente_sdk_id")->find($clienteId);
            $customerId=$BblSuscripcion->cliente_sdk_id;
            $dataCustomer=$this->getCustomerBbl($clienteId,$customerId);
            foreach($dataCustomer->data->cards as $card){
               if($card->default){
                    $tokenCard=$card->token;
               }
            }


            if(isset($tokenCard)){
            $datosTokenizacion=BblClientesCard::where('token',$tokenCard)->first();


            $docType =  $datosTokenizacion["doc_type"] ?? "";
            $docNumber =  $datosTokenizacion["doc_number"] ?? "";
            $address =  $datosTokenizacion["address"] ?? "";
            $phone =  $datosTokenizacion["phone"] ?? "";
            $cellPhone =  $datosTokenizacion["cell_phone"] ?? "";

            $charge = [
                "id_plan" =>  $idPlan,
                "customer" => $customerId,
                "token_card" => $tokenCard,
                "doc_type" => $docType,
                "doc_number" => $docNumber, 
                "address" => $address,
                "phone" => $phone,
                "cell_phone" => $cellPhone,
                "ip" => $ip,
            ];

            if($test){ //para efectos de pruebas
                $data=$this->createSubscriptionsBbl($charge);
                $data=$this->cancelSubscriptionsBbl($data->id,$clienteId);
                //exit();
            }

            $response = $this->chargeBbl($charge, $clienteId,true);
            
            if (!$response) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error en transaccion';
                $arrResponse['textResponse'] = 'Error en transaccion';
                $arrResponse['lastAction'] = 'charge';
                $arrResponse['data'] = ["error" => "Error en transaccion"];
                return $arrResponse;
            }
          
            if (!isset($response->success)) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error en transaccion';
                $arrResponse['textResponse'] = "Error transaccion" . $response->message;
                $arrResponse['lastAction'] = 'charge';
                $arrResponse['data'] = ["error" => $response->data];
                return $arrResponse;
            }
            if($response->success){
                $stateCode=$response->data->cod_respuesta;
                
                $response->data->chargeStatus = $this->setSubscriptionState($response->data->estado);
                
                
                if($stateCode !== TransactionStateCodes::ACCEPTED && $stateCode !== TransactionStateCodes::PENDING){
                    $arrResponse['success'] = false;
                    $arrResponse['titleResponse'] = 'Error charge';
                    $arrResponse['textResponse'] = "Error transaccion " . $response->data->estado;
                    $arrResponse['lastAction'] = 'charge';
                    $arrResponse['data'] = ["error" => $response->data];
                    return $arrResponse;  
                }
            }
            try{  
                $suscripcionId=$response->data->extras->extra1;
                $bblSuscripcion= BblSuscripcion::where("suscripcion_sdk_id",$suscripcionId)
                    ->where("estado",7)
                    ->first();
                if ($bblSuscripcion) {
                    $bblSuscripcion->estado=5;
                    $bblSuscripcion->save();
                }

                $BblSuscripcionCargos = new BblSuscripcionCargos();

                $detailTransaction=$this->getTransactionBbl($response->data->ref_payco);

                $BblSuscripcionCargos->suscription_id           =$suscripcionId;
                $BblSuscripcionCargos->suscripcion_cliente_id   =$response->data->extras->extra2;
                $BblSuscripcionCargos->ref_payco                =$response->data->ref_payco;
                $BblSuscripcionCargos->factura                  =$response->data->factura;
                $BblSuscripcionCargos->descripcion              =$response->data->descripcion;
                $BblSuscripcionCargos->valor                    =$response->data->valor;
                $BblSuscripcionCargos->valor_neto               =$response->data->valorneto;
                $BblSuscripcionCargos->moneda                   =$response->data->moneda;
                $BblSuscripcionCargos->respuesta                =$response->data->respuesta;
                $BblSuscripcionCargos->recibo                   =$response->data->recibo;
                $BblSuscripcionCargos->fecha                    =$response->data->fecha;
                $BblSuscripcionCargos->estado                   =$response->data->estado;
                $BblSuscripcionCargos->confirmacion             =false;
                $BblSuscripcionCargos->iva                      = $detailTransaction->data->x_tax;
                $BblSuscripcionCargos->tarjeta_nro              = $detailTransaction->data->x_cardnumber;
                $BblSuscripcionCargos->tarjeta_franquicia       = $response->data->franquicia;

                $BblSuscripcionCargos->save();


            }catch(\Exception $exception){

                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error save charge database';
                $arrResponse['textResponse'] = 'Error save charge database';
                $arrResponse['lastAction'] = 'save_charge';
                $arrResponse['data'] = ["error" => "Error save charge"];
                return $arrResponse;

            }


            $success = true;
            $titleResponse = 'response from plan';
            $textResponse = "response from plan";
            $lastAction = 'charge';
            $data = $response;
        }else{
            $success = false;
            $titleResponse = 'Not card';
            $textResponse = "Not card";
            $lastAction = 'changePlan';
            $arrResponse['data'] = ["error" => "has no registered cards"];
        }

        } catch (Exception $exception) {
            $success = false;
            $titleResponse = 'Error';
            $textResponse = "Error inesperado al consultar las transacciones con los parametros datos";
            $lastAction = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }


        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $titleResponse;
        $arrResponse['textResponse'] = $textResponse;
        $arrResponse['lastAction'] = $lastAction;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }


    public function setSubscriptionState($state)
    {
        $output = 0;
        switch ($state) {
            case 'Aceptada':
                $output = 1;
                break;

            case 'Pendiente':
                $output = 5;
                break;

            case 'Rechazada':
                $output = 3;
                break;

            case 'Reversada':
                $output = 7;
                break;

            case 'Fallida':
                $output = 3;
                break;

        }

        return $output;
    }
}