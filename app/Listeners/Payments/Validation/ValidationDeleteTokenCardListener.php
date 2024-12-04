<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationDeleteTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationDeleteTokenCardListener extends HelperPago
{

    /**
     * ValidationDeleteTokenCardListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationDeleteTokenCardEvent $event
     * @return array
     */
    public function handle(ValidationDeleteTokenCardEvent $event)
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


        //// franchise ///////////
        if (isset($data['franchise'])) {
            $franchise = $data['franchise'];
        } else {
            $franchise = false;
        }
        if (isset($franchise)) {
            $vfranchise = $validate->ValidateVacio($franchise, 'franchise');
            if (!$vfranchise) {
                $validate->setError(500, "field franchise required");
            } else {
                if (is_string($franchise)) {
                    $arrResponse['franchise'] = (string)$franchise;
                } else {
                    $validate->setError(500, "field franchise is type string");
                }
            }
        } else {
            $validate->setError(500, "field franchise required");
        }
        ///// franchise ///////////

        //// mask ///////////
        if (isset($data['mask'])) {
            $mask = $data['mask'];
        } else {
            $mask = false;
        }
        if (isset($mask)) {
            $vmask = $validate->ValidateVacio($mask, 'mask');
            if (!$vmask) {
                $validate->setError(500, "field mask required");
            } else {
                if (is_string($mask)) {
                    $arrResponse['mask'] = (string)$mask;
                } else {
                    $validate->setError(500, "field mask is type string");
                }
            }
        } else {
            $validate->setError(500, "field mask required");
        }
        ///// mask ///////////


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
            $this->saveLog(2, $clientId, '', $response, 'transaction_tc_split_payments');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;

    }
}