<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessDeleteTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessDeleteTokenCardListener extends HelperPago
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
     * @param ProcessDeleteTokenCardEvent $event
     * @return mixed
     */
    public function handle(ProcessDeleteTokenCardEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $franchise = $fieldValidation["franchise"];
            $mask = $fieldValidation["mask"];
            $customerId = $fieldValidation["customerId"];

            ////Llamar el metodo que realiza la tokenización de la tarjeta.
            $card = ["franchise" => $franchise, "mask" => $mask, "customer_id" => $customerId, "ip"=> "127.0.0.1",];
            $responseDeleteTokenCard = $this->deleteTokenMongoDb($card, $clientId);
            if (!$responseDeleteTokenCard) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error delete token card';
                $arrResponse['textResponse'] = 'Error delete token card';
                $arrResponse['lastAction'] = 'delete_token_card';
                $arrResponse['data'] = ["error" => "Error valid token"];
                return $arrResponse;
            }
            if (!$responseDeleteTokenCard->status) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error delete token card';
                $arrResponse['textResponse'] = "Error delete token card" . $responseDeleteTokenCard->message;
                $arrResponse['lastAction'] = 'delete_token_card';
                $arrResponse['data'] = ["error" => $responseDeleteTokenCard->data];
                return $arrResponse;
            }

            $success = true;
            $title_response = 'Success token delete';
            $text_response = "Success token delete";
            $last_action = 'delete_token_card';
            $data = $responseDeleteTokenCard;

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