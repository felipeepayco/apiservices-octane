<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationTokenCardListener extends HelperPago
{

    /**
     * ValidationTokenCardListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationTokenCardEvent $event
     * @return array
     */
    public function handle(ValidationTokenCardEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        if(isset($data['token'])){
            $arrResponse['token']=$data['token'];
        }
        ///Validar parametros obligatorios ///////////////////////////////////////////////
        if (isset($data['clientId'])) {
            $clientId = $data['clientId'];
        } else {
            $clientId = false;
        }

        ///// clientId /////////////////
        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else {
                $arrResponse['clientId'] = $clientId;
            }
        } else {
            $validate->setError(500, "field clientId required");
        }
        ///// clientId /////////////////

        $obligatorios=["docType","docNumber","address","phone","cellPhone","name", "lastname"];
        foreach($obligatorios as $obligatorio){
            if(isset($data[$obligatorio]) && $validate->ValidateVacio($data[$obligatorio], null)){
                $arrResponse[$obligatorio] = $data[$obligatorio];
            }else{
                $validate->setError(500, "field $obligatorio required");
            }
        }
        
        //// cardNumber ///////////
        if (isset($data['cardNumber'])) {
            $cardNumber = $data['cardNumber'];
        } else {
            $cardNumber = false;
        }
        if (isset($cardNumber)) {
            $vcardNumber = $validate->ValidateVacio($cardNumber, 'cardNumber');
            if (!$vcardNumber) {
                $validate->setError(500, "field cardNumber required");
            } else {
                if (is_string($cardNumber)) {
                    $arrResponse['cardNumber'] = (string)$cardNumber;
                } else {
                    $validate->setError(500, "field cardNumber is type string");
                }
            }
        } else {
            $validate->setError(500, "field cardNumber required");
        }
        ///// cardNumber ///////////

        //// cardExpYear ///////////
        if (isset($data['cardExpYear'])) {
            $cardExpYear = $data['cardExpYear'];
        } else {
            $cardExpYear = false;
        }
        if (isset($cardExpYear)) {
            $vcardExpYear = $validate->ValidateVacio($cardExpYear, 'cardExpYear');
            if (!$vcardExpYear) {
                $validate->setError(500, "field cardExpYear required");
            } else {
                if (is_string($cardExpYear)) {
                    if (strlen($cardExpYear) < 4) {
                        $validate->setError(500, "Example: '2020' - length must be 4 characters long");
                    } else {
                        $arrResponse['cardExpYear'] = (string)$cardExpYear;
                    }
                } else {
                    $validate->setError(500, "field cardExpYear is type string");
                }
            }
        } else {
            $validate->setError(500, "field cardExpYear required");
        }
        ///// cardExpYear ///////////

        //// cardExpMonth ///////////
        if (isset($data['cardExpMonth'])) {
            $cardExpMonth = $data['cardExpMonth'];
        } else {
            $cardExpMonth = false;
        }
        if (isset($cardExpMonth)) {
            $vcardExpMonth = $validate->ValidateVacio($cardExpMonth, 'cardExpMonth');
            if (!$vcardExpMonth) {
                $validate->setError(500, "field cardExpMonth required");
            } else {
                if (is_string($cardExpMonth)) {
                    if (strlen($cardExpYear) < 2) {
                        $validate->setError(500, "Example: '12' - length must be 2 characters long");
                    } else {
                        $arrResponse['cardExpMonth'] = (string)$cardExpMonth;
                    }
                } else {
                    $validate->setError(500, "field cardExpMonth is type string");
                }
            }
        } else {
            $validate->setError(500, "field cardExpMonth required");
        }
        ///// cardExpMonth ///////////

        //// cardCvc ///////////
        if (isset($data['cardCvc'])) {
            $cardCvc = $data['cardCvc'];
        } else {
            $cardCvc = false;
        }
        if (isset($cardCvc)) {
            $vcardCvc = $validate->ValidateVacio($cardCvc, 'cardCvc');
            if (!$vcardCvc) {
                $validate->setError(500, "field cardCvc required");
            } else {
                if (is_string($cardCvc)) {
                    $arrResponse['cardCvc'] = (string)$cardCvc;
                } else {
                    $validate->setError(500, "field cardCvc is type string");
                }
            }
        } else {
            $validate->setError(500, "field cardCvc required");
        }
        ///// cardCvc ///////////


        if ($validate->totalerrors > 0) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => $data
            );
            $this->saveLog(2, $clientId, '', $response, 'transaction_tc_split_payments');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;

    }
}