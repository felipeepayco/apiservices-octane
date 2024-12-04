<?php

namespace App\Http\Controllers;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;
use App\Events\Country\Process\ProcessGetConfCountriesEvent;

class ApiConfCountryController extends HelperPago
{

    public function __construct(Request $request)
    {
        //comment
    }

    public function getConfCountries(Request $request)
    {
        try {
            $confCountries = event(
                new ProcessGetConfCountriesEvent($request),
                $request
            );

            $response = array(
                'success' => $confCountries[0]['success'],
                'titleResponse' => $confCountries[0]['titleResponse'],
                'textResponse' => $confCountries[0]['textResponse'],
                'lastAction' => $confCountries[0]['lastAction'],
                'data' => $confCountries[0]['data']
            );
        } catch (\Exception $exception) {
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);

            $response = array(
                'success' => false,
                'titleResponse' => "Error",
                'textResponse' => "Error inesperado al consultar la informacion " . $exception->getMessage(),
                'lastAction' => "Consult config countries",
                'data' => array(
                    'totalerrores' => $validate->totalerrors,
                    'errores' => $validate->errorMessage
                )
            );
        }

        return $this->crearRespuesta($response);
    }
}
