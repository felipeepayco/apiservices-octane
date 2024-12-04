<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessTokenCardV2Event;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\BblClientes;
use App\Models\BblClientesCard;
use Illuminate\Support\Facades\DB;

class ProcessTokenCardV2Listener extends HelperPago
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
    public function handle(ProcessTokenCardV2Event $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $token=$fieldValidation["token"];

            $clientId = $fieldValidation["clientId"];
            $cardNumber =  $this->desencriptar($fieldValidation["cardNumber"],$token);
            $cardExpYear =  $this->desencriptar($fieldValidation["cardExpYear"],$token);
            $cardExpMonth =  $this->desencriptar($fieldValidation["cardExpMonth"],$token);
            $cardCvc =  $this->desencriptar($fieldValidation["cardCvc"],$token);

            $docType =  $this->desencriptar($fieldValidation["docType"],$token);
            $docNumber =  $this->desencriptar($fieldValidation["docNumber"],$token);
            $address =  $this->desencriptar($fieldValidation["address"],$token);
            $phone =  $this->desencriptar($fieldValidation["phone"],$token);
            $cellPhone =  $this->desencriptar($fieldValidation["cellPhone"],$token);
            $name =  $this->desencriptar($fieldValidation["name"],$token);
            $lastname =  $this->desencriptar($fieldValidation["lastname"],$token);
            

            

            ////Llamar el metodo que realiza la tokenización de la tarjeta.
            $card = ["number" => $cardNumber, "expYear" => $cardExpYear, "expMonth" => $cardExpMonth, "cvc" => $cardCvc, "ip" => "127.0.0.1", "v4l1d4t3" => true];
            $responseTokenCard = $this->tokenMongoDbBbl($card, $clientId,true);
            if (!$responseTokenCard) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error token card';
                $arrResponse['textResponse'] = 'Error token card';
                $arrResponse['lastAction'] = 'token_card';
                $arrResponse['data'] = ["error" => "Error valid token"];
                return $arrResponse;
            }
            if (!$responseTokenCard->status) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error token card';
                $arrResponse['textResponse'] = "Error token card" . $responseTokenCard->message;
                $arrResponse['lastAction'] = 'token_card';
                $arrResponse['data'] = ["error" => $responseTokenCard->data];
                return $arrResponse;
            }

            try{
              
                 $BblClientesCard= new BblClientesCard();  

                 $BblClientesCard->bbl_cliente_id=$clientId;
                 $BblClientesCard->token=$responseTokenCard->id;
                 $BblClientesCard->card_number="";
                 $BblClientesCard->card_exp_year="";
                 $BblClientesCard->card_exp_month="";
                 $BblClientesCard->mask=$responseTokenCard->card->mask;
                 $BblClientesCard->name=$responseTokenCard->card->name;

                 $BblClientesCard->doc_type= $docType;
                 $BblClientesCard->doc_number=$docNumber;
                 $BblClientesCard->address=$address;
                 $BblClientesCard->phone=$phone;
                 $BblClientesCard->cell_phone=$cellPhone;
                 $BblClientesCard->firstname=$name;
                 $BblClientesCard->lastname=$lastname;

                 $BblClientesCard->save();
                 
                 $client = BblClientes::find($clientId);

                 /*$this->EmailNotificationsBBL($client->email, "email/send-added-card",
                 [
                     "clientName" => $client->nombre . " " . $client->apellido,
                     "cardFranchise" => $this->imgFranchise($BblClientesCard->name),
                     "cardLastNumbers" => substr($BblClientesCard->mask, -4),
                     
                    ]);*/
                 $this->sendEmailCardSuscription(
                    $client,$responseTokenCard->card,
                    "Medio de pago agreado: Haz agregado una nueva tarjeta",
                    "babilonia_tarjeta_agregada"
                 );

            }catch(\Exception $exception){

                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error save token database';
                $arrResponse['textResponse'] = 'Error save token database';
                $arrResponse['lastAction'] = 'save_token_card';
                $arrResponse['data'] = ["error" => "Error save token"];
                return $arrResponse;

            }


            $success = true;
            $title_response = 'Success token generate';
            $text_response = "Success token generate";
            $last_action = 'token_card';
            $data = $responseTokenCard;

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

    private function desencriptar($encripData,$token){
        $cipher="AES-128-CBC";
        $iv=substr(env('JWT_SECRET'), 0, 16);
        $key=substr($token,0,16);
        return openssl_decrypt($encripData, $cipher, $key, 0, $iv);

    }


}