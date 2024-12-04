<?php

namespace App\Http\Controllers;

use App\Events\ConsultProfileEditEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Models\GrantUser;
use Illuminate\Http\Request;

class ApiProfileController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function editProfile(Request $request)
    {
        try {
            $consultProfileUpdate = event(
                new ConsultProfileEditEvent($request->all()),
                $request
            );

            $success = $consultProfileUpdate[0]['success'];
            $title_response = $consultProfileUpdate[0]['titleResponse'];
            $text_response = $consultProfileUpdate[0]['textResponse'];
            $last_action = $consultProfileUpdate[0]['lastAction'];
            $data = $consultProfileUpdate[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

   
}