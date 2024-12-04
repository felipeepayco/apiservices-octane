<?php

namespace App\Listeners\Invoice\Validation;

use App\Events\Invoice\Validation\ValidationInvoiceCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationInvoiceCreateListener extends HelperPago
{
    /**
     * ValidationInvoiceCreateListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @param ValidationInvoiceCreateEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationInvoiceCreateEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;


        $clientId = CommonValidation::validateIsSet($data, 'clientId', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', 'empty');

        $projectId = CommonValidation::validateIsSet($data, 'projectId', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $projectId, 'projectId', 'empty');

        $clientIdentifier = CommonValidation::validateIsSet($data, 'clientIdentifier', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientIdentifier, 'clientIdentifier', 'empty');

        $details = CommonValidation::validateIsSet($data, 'details', false, 'array');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $details, 'details', 'array');


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

            $this->saveLog(2,$clientId, '', $response, 'invoice_new_file');
            return $response;
        }

        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }
}
