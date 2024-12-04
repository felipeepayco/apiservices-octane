<?php

namespace App\Listeners\PaycoLink\Validation;

use App\Events\PaycoLink\Validation\ValidationPaycoLinkPaymentCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;



class ValidationPaycoLinkPaymentCreateListener extends HelperPago
{

    /**
     * ValidationPaycoLinkPaymentCreateListener constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param ValidationPaycoLinkPaymentCreateEvent $event
     * @return array
     */
    public function handle(ValidationPaycoLinkPaymentCreateEvent $event)
    {
        
        $data = $event->arr_parametros;
        $arrResponse = [];
        $clientId = (isset($data['clientId'])) ? $data['clientId'] : false;
        $validateFields = $this->validateFields($data);        

        if ($validateFields['validate']->totalerrors > 0) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';
            $response = array(
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => [
                    'totalErrors' => $validateFields['validate']->totalerrors,
                    'errors' => $validateFields['validate']->errorMessage
                ]
            );

            $this->optionalSaveLog('response', $response, $clientId,'paycolink_create_payment', $data['log_session']);

            return $response;
        }

        $arrResponse = $validateFields['arrResponse'];
        $arrResponse['success'] = true;

        return $arrResponse;

    }

    private function validateFields($data)
    {
        $validate = new Validate();
        //// transaction
        $transaction = isset($data['transaction']) ? $data['transaction'] : false;
        if (isset($transaction)) {
            $vtransaction = $validate->ValidateVacio($transaction, 'transaction');
            if (!$vtransaction) {
                $validate->setError(500, "field transaction required");
            } else {
                $arrResponse['transaction'] = (int)$transaction;
            }
        } else {
            $validate->setError(500, "field transaction required");
        }
        //// paycoLinkId
        $paycoLinkId = isset($data['paycoLinkId']) ? $data['paycoLinkId'] : false;
        if (isset($paycoLinkId)) {
            $vpaycoLinkId  = $validate->ValidateVacio($paycoLinkId, 'paycoLinkId');
            if (!$vpaycoLinkId) {
                $validate->setError(500, "field paycoLinkId required");
            } else {
                $arrResponse['paycoLinkId'] = (int)$paycoLinkId;
            }
        } else {
            $validate->setError(500, "field paycoLinkId required");
        }

        //// paycoLinkId
        $quantity = isset($data['quantity']) ? $data['quantity'] : false;
        if (isset($quantity)) {
            $vquantity  = $validate->ValidateVacio($quantity, 'quantity');
            if (!$vquantity) {
                $validate->setError(500, "field quantity required");
            } else {
                $arrResponse['quantity'] = (int)$quantity;
            }
        } else {
            $validate->setError(500, "field quantity required");
        }

        return [ 
            'validate' => $validate,
            'arrResponse' => $arrResponse
        ];
    }

}