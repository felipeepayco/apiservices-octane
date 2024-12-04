<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessTokenCustomerEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessTokenCustomerListener extends HelperPago
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
    public function handle(ProcessTokenCustomerEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];

            $data = $this->getData($fieldValidation);


            ////Crear el cliente con el token de la tarjeta de credito
            $responseCustomer = $this->customerMongoDb($clientId, $data);
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
                $arrResponse['lastAction'] = 'create_customer';
                $arrResponse['data'] = ["error" => $responseCustomer->data];
                return $arrResponse;
            }

            $success = true;
            $title_response = 'Success token generate';
            $text_response = "Success token generate";
            $last_action = 'token_customer';
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

        $docType = $fieldValidation["docType"];
        $docNumber = $fieldValidation["docNumber"];
        $name = $fieldValidation["name"];
        $lastName = $fieldValidation["lastName"];
        $email = $fieldValidation["email"];
        $address = $fieldValidation["address"];
        $phone = $fieldValidation["phone"];
        $cellPhone = $fieldValidation["cellPhone"];
        $city = $fieldValidation["city"];


        $requireCardToken = $fieldValidation["requireCardToken"];

        $data = array(
            "doc_type" => $docType,
            "doc_number" => $docNumber,
            "name" => $name,
            "last_name" => $lastName,
            "email" => $email,
            "default" => true,
            "ip" => '127.0.0.1'
        );

        if ($requireCardToken) {
            $cardTokenId = $fieldValidation["cardTokenId"];
            $data["token_card"] = $cardTokenId;
        } else {
            $data["require_card"] = false;
        }

        if ($phone) {
            $data["phone"] = $phone;
        }
        if ($cellPhone) {
            $data["cell_phone"] = $cellPhone;
        }
        if ($address) {
            $data["address"] = $address;
        }
        if ($city) {
            $data["city"] = $city;
        }

        return $data;
    }

}