<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationTransactionDPEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Http\Validation\ValidatorCommon;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;

class ValidationTransactionDPListener extends HelperPago
{

    /**
     * @param ValidationTransactionDPEvent $event
     * @return array
     */
    public function handle(ValidationTransactionDPEvent $event)
    {
        $data = $event->arr_parametros;
        $validate = new Validate();
        $validatorCommon = new ValidatorCommon($validate);

        $response['clientId'] = $data['clientId'];

        $docType = $validatorCommon->validateIsSet($data, 'docType', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $docType, 'docType', ValidatorCommon::STRING, true);

        $document = $validatorCommon->validateIsSet($data, 'document', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $document, 'document', ValidatorCommon::STRING, true);

        $name = $validatorCommon->validateIsSet($data, 'name', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $name, 'name', ValidatorCommon::EMPTY, true);

        $lastName = $validatorCommon->validateIsSet($data, 'lastName', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $lastName, 'lastName', ValidatorCommon::EMPTY, true);

        $email = $validatorCommon->validateIsSet($data, 'email', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $email, 'email', ValidatorCommon::EMAIL, true);
        
        $indCountry = $validatorCommon->validateIsSet($data, 'indCountry', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $indCountry, 'indCountry', ValidatorCommon::STRING, true);

        $phone = $validatorCommon->validateIsSet($data, 'phone', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $phone, 'phone', ValidatorCommon::EMPTY, true);
        $validatorCommon->validateParamFormat($response, $phone, 'phone', ValidatorCommon::PHONE, true);

        $country = $validatorCommon->validateIsSet($data, 'country', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $country, 'country', ValidatorCommon::STRING, true);

        $city = $validatorCommon->validateIsSet($data, 'city', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $city, 'city', ValidatorCommon::STRING, true);

        $address = $validatorCommon->validateIsSet($data, 'address', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $address, 'address', ValidatorCommon::EMPTY, true);

        $ip = $validatorCommon->validateIsSet($data, 'ip', ValidatorCommon::STRING, "127.0.0.1");
        $validatorCommon->validateParamFormat($response, $ip, 'ip', ValidatorCommon::EMPTY, false);

        $currency = $validatorCommon->validateIsSet($data, 'currency', ValidatorCommon::STRING, "COP");
        $validatorCommon->validateParamFormat($response, $currency, 'currency', ValidatorCommon::STRING, false);
        
        $hash = md5(microtime()) . rand(0, 99999999);
        $invoice = $validatorCommon->validateIsSet($data, 'invoice', ValidatorCommon::STRING, "QR-APIFY-DAVIPLATA".$hash);
        if($invoice == ""){
            $invoice = "QR-APIFY-DAVIPLATA".$hash;
        }
        $validatorCommon->validateParamFormat($response, $invoice, 'invoice', ValidatorCommon::STRING, false);

        $description = $validatorCommon->validateIsSet($data, 'description', ValidatorCommon::STRING, "");
        $validatorCommon->validateParamFormat($response, $description, 'description', ValidatorCommon::STRING, false);

        $value = $validatorCommon->validateIsSet($data, 'value', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $value, 'value', ValidatorCommon::EMPTY, true);

        $tax = $validatorCommon->validateIsSet($data, 'tax', ValidatorCommon::FLOAT, 0.0);
        $validatorCommon->validateParamFormat($response, $tax, 'tax', ValidatorCommon::FLOAT, false);

        $ico = $validatorCommon->validateIsSet($data, 'ico', ValidatorCommon::FLOAT, 0.0);
        $validatorCommon->validateParamFormat($response, $ico, 'ico', ValidatorCommon::FLOAT, false);

        $taxBase = $validatorCommon->validateIsSet($data, 'taxBase', ValidatorCommon::FLOAT, 0.0);
        $validatorCommon->validateParamFormat($response, $taxBase, 'taxBase', ValidatorCommon::FLOAT, false);
        
        $testMode = $validatorCommon->validateIsSet($data, 'testMode', ValidatorCommon::BOOL, false);
        $validatorCommon->validateParamFormat($response, $testMode, 'testMode', ValidatorCommon::BOOL, false);

        $urlResponse = $validatorCommon->validateIsSet($data, 'urlResponse', ValidatorCommon::STRING, "");
        $validatorCommon->validateParamFormat($response, $urlResponse, 'urlResponse', ValidatorCommon::STRING, false);

        $urlResponsePointer = $validatorCommon->validateIsSet($data, 'urlResponsePointer', ValidatorCommon::STRING, "");
        $validatorCommon->validateParamFormat($response, $urlResponsePointer, 'urlResponsePointer', ValidatorCommon::STRING, false);

        $urlConfirmation = $validatorCommon->validateIsSet($data, 'urlConfirmation', ValidatorCommon::STRING, "");
        $validatorCommon->validateParamFormat($response, $urlConfirmation, 'urlConfirmation', ValidatorCommon::STRING, false);

        $methodConfirmation = $validatorCommon->validateIsSet($data, 'methodConfirmation', ValidatorCommon::STRING);
        $validatorCommon->validateParamFormat($response, $methodConfirmation, 'methodConfirmation', ValidatorCommon::STRING, true);

        $typeIntegration = $validatorCommon->validateIsSet($data, 'typeIntegration', ValidatorCommon::STRING, 'apify');
        $validatorCommon->validateParamFormat($response, $typeIntegration, 'typeIntegration', ValidatorCommon::STRING, false);

        for ($i = 1; $i <= 10; $i++) {
            $extra = $validatorCommon->validateIsSet($data, "extra{$i}", ValidatorCommon::STRING, "");
            $validatorCommon->validateParamFormat($response, $extra, "extra{$i}", ValidatorCommon::STRING, false);
        }

       
        if($validatorCommon->responseErrors('transaccion_daviplata', $response['clientId']) !== null) {
            return $validatorCommon->responseErrors();
        }

        $response['success'] = true;

        return $response;

    }
}