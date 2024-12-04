<?php

namespace App\Listeners;


use App\Events\ValidationGeneralPayPalAssociateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralPayPalAssociateListener extends HelperPago
{

    /**
     * ValidationGeneralCatalogueNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationGeneralPayPalAssociateEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationGeneralPayPalAssociateEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

        if (isset($data['clientId'])) {
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false;
        }

        if (isset($data['code'])) {
            $code = (string)$data['code'];
        } else {
            $code = false;
        }

        if (isset($data['apiResponse'])) {
            $apiResponse = (string)$data['apiResponse'];
        } else {
            $apiResponse = false;
        }


        if (isset($code)) {
            $vcode = $validate->ValidateVacio($code, 'code');
            if (!$vcode) {
                $validate->setError(500, "field code required");
            } else {
                $arr_respuesta['code'] = $code;
            }
        } else {
            $validate->setError(500, "field code required");
        }

        if (isset($apiResponse)) {
            $vapiResponse = $validate->ValidateVacio($apiResponse, 'apiResponse');
            if (!$vapiResponse) {
                $validate->setError(500, "field apiResponse required");
            } else {
                $arr_respuesta['apiResponse'] = $apiResponse;
            }
        } else {
            $validate->setError(500, "field apiResponse required");
        }

        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else {
                $arr_respuesta['clientId'] = $clientId;
            }
        } else {
            $validate->setError(500, "field clientId required");
        }


        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation data paypal';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'title_response' => $title_response,
                'text_response' => $text_response,
                'last_action' => $last_action,
                'data' => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response, 'paypal_vinculate');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}