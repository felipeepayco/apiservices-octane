<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessCustomerEvent;
use App\Events\Payments\Process\ProcessCustomerUpdateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessCustomerUpdateListener extends HelperPago
{

    /**
     * ProcessCustomerUpdateListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ProcessCustomerUpdateEvent $event
     * @return mixed
     */
    public function handle(ProcessCustomerUpdateEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $customerId = $fieldValidation["customerId"];
            $name = $fieldValidation["name"];

            ////Crear el cliente con el token de la tarjeta de credito
            $data = ["name" => $name, "ip" => "127.0.0.1",];
            $responseCustomer = $this->customerUpdateMongoDb($clientId, $customerId, $data);
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
                $arrResponse['lastAction'] = 'update_customer';
                $arrResponse['data'] = ["error" => $responseCustomer->data];
                return $arrResponse;
            }

            $success = true;
            $title_response = 'Customer successfully updated';
            $text_response = "Customer successfully updated";
            $last_action = 'update_customer';
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

}