<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationCustomersEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationCustomersListener extends HelperPago
{

    /**
     * ValidationCustomersListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationCustomersEvent $event
     * @return array
     */
    public function handle(ValidationCustomersEvent $event)
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