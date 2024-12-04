<?php

namespace App\Listeners\MongoTransaction\Validation;

use App\Events\MongoTransaction\Validation\ValidationDataMongoTransactionEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Clientes;
use App\Models\LlavesClientes;
use Illuminate\Http\Request;

class ValidationDataMongoTransactionListener extends HelperPago
{
    public $validate;
    /**
     * ValidationDataMongoTransactionListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->validate = new Validate();
    }

    /**
     * @param ValidationDataMongoTransactionEvent $event
     * @return array
     */
    public function handle(ValidationDataMongoTransactionEvent $event)
    {
        try {
            $data = $this->keysToLowercase($event->arr_parametros);
            $arrResponse['success'] = false;
            if (isset($data['clientid'])) {
                $clientId = $data['clientid'];
                $arrResponse['epaycoKey'] = LlavesClientes::where('cliente_id',$clientId)->first()->public_key;
            } else {
                $clientId = false;
                $this->validate->setError(500, "field clientId required");
            }
        
            $fields = [
                ["name" => "name", "fieldlower" => "name" ,"required" => true, "validatorFunctionName" => "validateString"],
                ["name" => "description", "fieldlower" => "description" ,"required" => true, "validatorFunctionName" => "validateString"],
                ["name" => "currency", "fieldlower" => "currency" ,"required" => true, "validatorFunctionName" => "validateString"],
                ["name" => "amount", "fieldlower" => "amount" ,"required" => true, "validatorFunctionName" => "validateString"],
                ["name" => "taxBase", "fieldlower" => "taxbase" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "tax", "fieldlower" => "tax" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "country", "fieldlower" => "country" ,"required" => true, "validatorFunctionName" => "validateString"],
                ["name" => "lang", "fieldlower" => "lang" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "methodsDisable", "fieldlower" => "methodsdisable" ,"required" => false, "validatorFunctionName" => "ValidateArray"],
                ["name" => "response", "fieldlower" => "response" ,"required" => false, "validatorFunctionName" => "ValidateUrl"],
                ["name" => "confirmation", "fieldlower" => "confirmation" ,"required" => false, "validatorFunctionName" => "ValidateUrl"],
                ["name" => "test", "fieldlower" => "test" ,"required" => true, "validatorFunctionName" => "isBoolean"],
                ["name" => "ip", "fieldlower" => "ip" ,"required" => true, "validatorFunctionName" => "ValidateIp"],
                ["name" => "invoice", "fieldlower" => "invoice" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "extra1", "fieldlower" => "extra1" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "extra2", "fieldlower" => "extra2" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "extra3", "fieldlower" => "extra3" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "acepted", "fieldlower" => "acepted" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "rejected", "fieldlower" => "rejected" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "pending", "fieldlower" => "pending" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "method", "fieldlower" => "method" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "autoclick", "fieldlower" => "autoclick" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "emailBilling", "fieldlower" => "emailbilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "nameBilling", "fieldlower" => "namebilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "addressBilling", "fieldlower" => "addressbilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "typeDocBilling", "fieldlower" => "typedocbilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "mobilephoneBilling", "fieldlower" => "mobilephonebilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "numberDocBilling", "fieldlower" => "numberdocbilling" ,"required" => false, "validatorFunctionName" => "validateString"],
                ["name" => "taxIco", "fieldlower" => "taxico" ,"required" => false, "validatorFunctionName" => "validateString"],
            ];
            if(isset($data["splitpayment"])){

                $fieldSplitPayment = [
                    ["name" => "splitpayment", "fieldlower" => "splitpayment","required" => true, "validatorFunctionName" => "isBoolean"],
                    ["name" => "splitAppId", "fieldlower" => "splitappid","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitMerchantId", "fieldlower" => "splitmerchantid","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitType", "fieldlower" => "splittype","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitPrimaryReceiver", "fieldlower" => "splitprimaryreceiver","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitPrimaryReceiver_fee", "fieldlower" => "splitprimaryreceiver_fee","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitRule", "fieldlower" => "splitrule","required" => true, "validatorFunctionName" => "ValidateVacio"],
                    ["name" => "splitReceivers", "fieldlower" => "splitreceivers","required" => true, "validatorFunctionName" => "ValidateVacio"],
                ];

                $fields = array_merge($fields,$fieldSplitPayment);
            }

            foreach ($fields as $field ) {
                $this->validateFieldMongoTransaction($field, $data, $arrResponse);
            }

            if ($this->validate->totalerrors > 0) {
                $success = false;
                $lastAction = 'validation data';
                $titleResponse = 'Error';
                $textResponse = 'Some fields are required, please correct the errors and try again';

                return [
                    'success' => $success,
                    'titleResponse' => $titleResponse,
                    'textResponse' => $textResponse,
                    'lastAction' => $lastAction,
                    'data' => [
                        'totalErrors' => $this->validate->totalerrors,
                        'errors' => $this->validate->errorMessage
                    ]
                ];
            }

            $arrResponse['success'] = true;
        } catch (\Throwable $th) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'error inesperado';

            return [
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => [
                    'totalErrors' => 1,
                    'errors' => $th->getMessage()
                ]
            ];
        }
        return $arrResponse;
    }

    private function validateFieldMongoTransaction($field, $data, &$arrResponse)
    {
        if (isset($data[$field["fieldlower"]])) {
            $value = $data[$field["fieldlower"]];
            if (!$this->validate->{$field["validatorFunctionName"]}($value, $field["fieldlower"])) {
                $this->validate->setError(500, "field {$field["name"]} with invalid type");
            } else {
                if (is_string($value)) {
                    $arrResponse["epayco" . ucfirst($field["name"])] = (string)$value;
                } else if(is_array($value)){
                    $arrResponse["epayco" . ucfirst($field["name"])] = $value;
                }else {
                    $this->validate->setError(500, "field {$field["name"]} is type string");
                }
            }
        } else if($field["required"]){
            $this->validate->setError(500, "field {$field["name"]} required");
        }
    }

    private function keysToLowercase($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $keyLowercase = strtolower($key);
            $newArray[$keyLowercase] = $value;
        }
        return $newArray;
    }
}