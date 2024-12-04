<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class RebootCertificateValidation
{
    public function validate(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();
        $arr_respuesta = [];
        $arr_respuesta['success'] = true;

        if (isset($data['id'])) {
            $id = (int)$data['id'];
        } else {
            $id = false;
        }

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

        if (isset($id)) {
            $vId = $validate->ValidateVacio($id, 'id');
            if (!$vId) {
                $validate->setError(500, "field id required");
            } else {
                $arr_respuesta['id'] = $id;
            }
        } else {
            $validate->setError(500, "field id required");
        }

        if ($validate->totalerrors > 0) {
            $success        = false;
            $last_action    = 'validation id y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data =
                array(
                    'totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            $arr_respuesta = array(
                'success'       => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'          => $data
            );

        }


        return $arr_respuesta;
    }
}   