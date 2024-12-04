<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessTokenCustomerDefaultTokenCardEvent;
use App\Events\Payments\Process\ProcessTokenCustomerNewTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessTokenCustomerDefaultTokenCardListener extends HelperPago
{

    /**
     * ProcessTokenCustomerDefaultTokenCardListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ProcessTokenCustomerDefaultTokenCardEvent $event
     * @return mixed
     */
    public function handle(ProcessTokenCustomerDefaultTokenCardEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];

            $data = $this->getData($fieldValidation);

            ////Crear el cliente con el token de la tarjeta de credito
            $responseCustomer = $this->addNewTokenDefaultCard($clientId, $data);
            if (!$responseCustomer) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error customer';
                $arrResponse['textResponse'] = 'Error customer';
                $arrResponse['lastAction'] = 'customer';
                $arrResponse['data'] = ["error" => "Error valid customer"];
                return $arrResponse;
            }
            if (!$responseCustomer->status) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error customer';
                $arrResponse['textResponse'] = "Error customer " . $responseCustomer->message;
                $arrResponse['lastAction'] = 'add_new_token_default';
                $arrResponse['data'] = ["error" => isset($responseCustomer->data) ? $responseCustomer->data : $responseCustomer];
                return $arrResponse;
            }

            $success = true;
            $title_response = 'Success add new token default';
            $text_response = "Success add new token default";
            $last_action = 'add_new_token_default';
            $data = $responseCustomer;

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

    private function getData($fieldValidation)
    {

        $tokenCard = $fieldValidation["cardToken"];
        $customerId = $fieldValidation["customerId"];
        $franchise = $fieldValidation["franchise"];
        $mask = $fieldValidation["mask"];

        $data = array(
            "token" => $tokenCard,
            "customer_id" => $customerId,
            "franchise" => $franchise,
            "mask" => $mask,
            "ip"=> "127.0.0.1",
        );

        return $data;
    }

}