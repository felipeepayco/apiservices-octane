<?php

namespace App\Listeners\MongoTransaction\Process;

use App\Events\MongoTransaction\Process\ProcessCreateMongoTransactionEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ProcessCreateMongoTransactionListener extends HelperPago
{

    /**
     * ProcessCreateMongoTransactionListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ProcessCreateMongoTransactionEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $url = getenv('BASE_URL_EPAYCO_CHECKOUT');
            $responseCurl = $this->apiService("{$url}/create/session", $fieldValidation, "POST");
            if(isset($responseCurl->success) && $responseCurl->success) {

                $success = true;
                $title_response = 'Sesión creada éxitosamente';
                $text_response = "Sesión creada éxitosamente";
                $last_action = 'create session';
                $data = ["sessionId" => $responseCurl->data->sessionId]; // Internamente se le llama transaccion de MongoDB, pero al cliente se le habla de SessionId para evitar confusión con la transaccion de MySQL
            } else {
                throw new Exception("Error Al crear la transacción - " . json_encode($responseCurl));
            }
            
        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error inesperado ';
            $text_response = $exception->getMessage();
            $last_action = 'Create Session';
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

    private function getData($fieldValidation, $cardTokenId)
    {

        $docType = $fieldValidation["docType"];
        $docNumber = $fieldValidation["docNumber"];
        $name = $fieldValidation["name"];
        $lastName = $fieldValidation["lastName"];
        $email = $fieldValidation["email"];
        $address = $fieldValidation["address"];
        $phone = $fieldValidation["phone"];
        $cellPhone = $fieldValidation["cellPhone"];
        $ip = $fieldValidation["ip"];

        $data = array(
            "doc_type" => $docType,
            "doc_number" => $docNumber,
            "token_card" => $cardTokenId,
            "name" => $name,
            "last_name" => $lastName,
            "email" => $email,
            "default" => true,
            "ip" => $ip,
        );

        if ($phone) {
            $data["phone"] = $phone;
        }
        if ($cellPhone) {
            $data["cell_phone"] = $cellPhone;
        }
        if ($address) {
            $data["address"] = $address;
        }

        return $data;
    }

    private function getDataTransaction(&$dataTransaction, $fieldValidation, $cardTokenId, $customerId)
    {
        $docType = $fieldValidation["docType"];
        $docNumber = $fieldValidation["docNumber"];
        $invoice = $fieldValidation["invoice"];
        $description = $fieldValidation["description"];
        $value = $fieldValidation["value"];
        $tax = $fieldValidation["tax"];
        $taxBase = $fieldValidation["taxBase"];
        $currency = $fieldValidation["currency"];
        $dues = $fieldValidation["dues"];
        $address = $fieldValidation["address"];
        $phone = $fieldValidation["phone"];
        $cellPhone = $fieldValidation["cellPhone"];
        $urlResponse = $fieldValidation["urlResponse"];
        $urlConfirmation = $fieldValidation["urlConfirmation"];
        $name = $fieldValidation["name"];
        $lastName = $fieldValidation["lastName"];
        $email = $fieldValidation["email"];
        $ip = $fieldValidation["ip"];
        $splitPayment = isset($fieldValidation["splitpayment"]) ? $fieldValidation["splitpayment"] : false;


        $dataTransaction = [
            "token_card" => $cardTokenId,
            "customer_id" => $customerId,
            "doc_type" => $docType,
            "doc_number" => $docNumber,
            "name" => $name,
            "last_name" => $lastName,
            "email" => $email,
            "bill" => $invoice,
            "currency" => $currency,
            "dues" => $dues,
            "value" => $value,
            "ip" => $ip,
        ];

        if ($description) {
            $dataTransaction["description"] = $description;
        }

        if ($tax) {
            $dataTransaction["tax"] = $tax;
        }

        if ($taxBase) {
            $dataTransaction["tax_base"] = $taxBase;
        }

        if ($address) {
            $dataTransaction["address"] = $address;
        }
        if ($phone) {
            $dataTransaction["phone"] = $phone;
        }
        if ($cellPhone) {
            $dataTransaction["cell_phone"] = $cellPhone;
        }
        if ($urlResponse) {
            $dataTransaction["url_response"] = $urlResponse;
        }
        if ($urlConfirmation) {
            $dataTransaction["url_confirmation"] = $urlConfirmation;
        }

        $dataTransaction["extras"] = [];
        for ($i = 1; $i <= 10; $i++) {
            if ($fieldValidation["extra{$i}"]) {
                $dataTransaction["extras"]["extra{$i}"] = $fieldValidation["extra{$i}"];
            }
        }

        if (count($dataTransaction["extras"]) <= 0) {
            unset($dataTransaction["extras"]);
        }

        if ($splitPayment) {
            $splitRule = $fieldValidation["splitRule"];
            $splitReceivers = $fieldValidation["splitReceivers"];
            $splitReceiversAmount = $fieldValidation["splitReceiversAmount"];
            $dataTransaction["split_payment"] = [
                "split_rule" => $splitRule,
                "split_receivers" => $splitReceivers,
                "split_receivers_amount" => $splitReceiversAmount
            ];
        }
    }
}