<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class CatalogueInactiveValidation
{

    public $response;

    public function validate(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();
        $arr_respuesta = [];

        if (isset($data['clientId'])) {
            $clientId = (int)$data['clientId'];
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

        $suspended = $validate->validateIsSet($data, 'suspended', false);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $suspended, 'suspended', '', false);

        $origin = $validate->validateIsSet($data, 'origin', false);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $origin, 'origin', '', false);

        if ($validate->totalerrors > 0) {
            $success        = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data =
                array(
                    'totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            $response = array(
                'success'       => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'          => $data
            );


            $this->response = $response;
            return false;
        }

        $arr_respuesta['success'] = true;

        $this->response = $arr_respuesta;
        return true;
    }
}
