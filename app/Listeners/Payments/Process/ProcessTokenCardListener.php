<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessTokenCardListener extends HelperPago
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
    public function handle(ProcessTokenCardEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $cardNumber = $fieldValidation["cardNumber"];
            $cardExpYear = $fieldValidation["cardExpYear"];
            $cardExpMonth = $fieldValidation["cardExpMonth"];
            $cardCvc = $fieldValidation["cardCvc"];

            ////Llamar el metodo que realiza la tokenización de la tarjeta.
            $card = ["number" => $cardNumber, "expYear" => $cardExpYear, "expMonth" => $cardExpMonth, "cvc" => $cardCvc, "ip" => "127.0.0.1", "v4l1d4t3" => true];
            $responseTokenCard = $this->tokenMongoDb($card, $clientId);
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

}