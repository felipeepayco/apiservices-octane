<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationDeleteTokenCardV2Event;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationDeleteTokenCardV2Listener extends HelperPago
{

    /**
     * ValidationChangePlanListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationChangePlanEvent $event
     * @return array
     */
    public function handle(ValidationDeleteTokenCardV2Event $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $obligatorios=["token","clientId"];
        foreach($obligatorios as $obligatorio){
            if(isset($data[$obligatorio]) && $validate->ValidateVacio($data[$obligatorio], null)){
                $arrResponse[$obligatorio] = $data[$obligatorio];
            }else{
                $validate->setError(500, "field $obligatorio required");
            }
        }

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
            $this->saveLog(2, $arrResponse["clientId"], '', $response, 'transaction_tc_split_payments');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;

    }
}