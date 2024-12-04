<?php

namespace App\Listeners\Catalogue\Validation;


use App\Events\Catalogue\Validation\ValidationGeneralCatalogueInactiveEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueInactiveListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /*
     *
     */
    public function handle(ValidationGeneralCatalogueInactiveEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $arr_respuesta = [];

        if (isset($data['clientId'])) {
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false;
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

        $suspended = $validate->validateIsSet($data,'suspended',false);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$suspended,'suspended','',false);

        $origin = $validate->validateIsSet($data,'origin',false);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$origin,'origin','',false);

        if ($validate->totalerrors > 0) {
            $success        = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success'       => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'          => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response, 'consult_delete_category');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}
