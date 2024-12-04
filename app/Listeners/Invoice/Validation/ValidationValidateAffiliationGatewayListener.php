<?php

namespace App\Listeners\Invoice\Validation;

use App\Events\Invoice\Validation\ValidationValidateAffiliationGatewayEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationValidateAffiliationGatewayListener extends HelperPago
{
    /**
     * ValidationValidateAffiliationGatewayListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @param ValidationValidateAffiliationGatewayEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationValidateAffiliationGatewayEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

        $clientId = CommonValidation::validateIsSet($data, 'clientId', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', 'empty');

        if ($validate->totalerrors > 0) {
            $response = [
                'success' => false,
                'titleResponse' => 'Error',
                'textResponse' => 'Some fields are required, please correct the errors and try again',
                'lastAction' => 'validation data save',
                'data' => [
                    'totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage,
                ],
            ];

            $this->saveLog(2,$clientId, '', $response, 'validate_affiliation_gateway');
            return $response;
        }

        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }
}
