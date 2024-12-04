<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessDeleteTokenCardV2Event;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use App\Models\BblClientesCard;
use Illuminate\Http\Request;

class ProcessDeleteTokenCardV2Listener extends HelperPago
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
     * @param ProcessDeleteTokenCardV2Event $event
     * @return mixed
     */
    public function handle(ProcessDeleteTokenCardV2Event $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $token = $fieldValidation["token"];
            $bblClientesCard=BblClientesCard::where('token',$token)->where('bbl_cliente_id',$clientId)->first();
            if(!isset($bblClientesCard)){
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error delete token card';
                $arrResponse['textResponse'] = "Error delete token card";
                $arrResponse['lastAction'] = 'delete_token_card';
                $arrResponse['data'] = ["error" => "Token no encontrado en la relación CLiente-Token de la base de datos"];
                return $arrResponse;     

            }
            $bblClientes=BblClientes::find($clientId);


            $mask =  $bblClientesCard["mask"];
            $franchise = $bblClientesCard["name"];
            $customerId = $bblClientes["cliente_sdk_id"];

            //VALIDAR TARJETA POR DEFECTO NO DEBE ELIMINARSE
            $dataCustomer=$this->getCustomerBblV2($customerId);
            foreach($dataCustomer->data->cards as $dataCard){
                if($dataCard->default==1){
                    if($dataCard->token==$token){
                        $arrResponse['success'] = false;
                        $arrResponse['titleResponse'] = 'Error delete token card';
                        $arrResponse['textResponse'] = "Error delete token card";
                        $arrResponse['lastAction'] = 'delete_token_card';
                        $arrResponse['data'] = ["error" => "No se debe eliminar la tarjeta por defecto"];
                        return $arrResponse;         
                    }
                }
            }
            $card = ["franchise" => $franchise, "mask" => $mask, "customer_id" => $customerId];
            $responseDeleteTokenCard = $this->deleteTokenMongoDb($card, $clientId);

            if($responseDeleteTokenCard->status==1 && $responseDeleteTokenCard->success==1){
                $bblClientesCard->status=0;
                $bblClientesCard->save();


                 $client = BblClientes::find($clientId);
                 /*$this->EmailNotificationsBBL($client->email, "email/send-deleted-card",
                 [
                     "clientName" => $client->nombre . " " . $client->apellido,
                     "cardFranchise" => $this->imgFranchise($bblClientesCard->name),
                     "cardLastNumbers" => substr($bblClientesCard->mask, -4),

                    ]);*/
                 $this->sendEmailCardSuscription(
                    $client,(object) $bblClientesCard,
                    "Medio de pago eliminado: Haz eliminado una tarjeta",
                    "babilonia_tarjeta_eliminada"
                 );


                $success = true;
                $title_response = 'Success token delete';
                $text_response = "Success token delete";
                $last_action = 'delete_token_card';
                $data = $responseDeleteTokenCard;
            }else{
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error delete token card';
                $arrResponse['textResponse'] = "Error delete token card" . $responseDeleteTokenCard->message;
                $arrResponse['lastAction'] = 'delete_token_card';
                $arrResponse['data'] = ["error" => $responseDeleteTokenCard->data];
                return $arrResponse;

            }

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar las transacciones con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }


        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $title_response;
        $arrResponse['textResponse'] = $text_response;
        $arrResponse['lastAction'] = $last_action;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

}