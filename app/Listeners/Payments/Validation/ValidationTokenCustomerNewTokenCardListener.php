<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationTokenCustomerNewTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationTokenCustomerNewTokenCardListener extends HelperPago
{

    /**
     * ValidationTokenCustomerNewTokenCardListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationTokenCustomerNewTokenCardEvent $event
     * @return array
     */
    public function handle(ValidationTokenCustomerNewTokenCardEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

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

        //// cardToken ///////////
        if (isset($data['cardToken'])) {
            $cardToken = $data['cardToken'];
        } else {
            $cardToken = false;
        }
        if (isset($cardToken)) {
            $vcardToken = $validate->ValidateVacio($cardToken, 'cardToken');
            if (!$vcardToken) {
                $validate->setError(500, "field cardToken required");
            } else {
                if (is_string($cardToken)) {
                    $arrResponse['cardToken'] = (string)$cardToken;
                } else {
                    $validate->setError(500, "field cardToken is type string");
                }
            }
        } else {
            $validate->setError(500, "field cardToken required");
        }
        ///// cardToken ///////////


        //// customerId ///////////
        if (isset($data['customerId'])) {
            $customerId = $data['customerId'];
        } else {
            $customerId = false;
        }
        if (isset($customerId)) {
            $vcustomerId = $validate->ValidateVacio($customerId, 'customerId');
            if (!$vcustomerId) {
                $validate->setError(500, "field customerId required");
            } else {
                if (is_string($customerId)) {
                    $arrResponse['customerId'] = (string)$customerId;
                } else {
                    $validate->setError(500, "field customerId is type string");
                }
            }
        } else {
            $validate->setError(500, "field customerId required");
        }
        ///// customerId ///////////

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
//            $this->saveLog(2, '', $response, 'transaction_tc_split_payments', $clientId);

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;

    }
}