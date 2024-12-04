<?php

namespace App\Http\Controllers;

use App\Events\PayPalAssociateEvent;
use App\Events\ValidationGeneralPayPalAssociateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiPayPalController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

 

    public function getPayPalAssociate(Request $request)
    {
        $arr_parametros = $request->request->all();

        $validationGeneralPayPalVinculate = event(
            new ValidationGeneralPayPalAssociateEvent($arr_parametros),
            $request);


        if (!$validationGeneralPayPalVinculate[0]["success"]) {
            return $this->crearRespuesta($validationGeneralPayPalVinculate[0]);
        }

        try {

            $payPalVinculate = event(
                new PayPalAssociateEvent($validationGeneralPayPalVinculate[0]),
                $request
            );

            $success = $payPalVinculate[0]['success'];
            $title_response = $payPalVinculate[0]['titleResponse'];
            $text_response = $payPalVinculate[0]['textResponse'];
            $last_action = $payPalVinculate[0]['lastAction'];
            $data = $payPalVinculate[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );

        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );

        $responseCurl = $this->apiService($arr_parametros["apiResponse"], $response, "POST");

        $client = new \stdClass();
        $client->cliente_id = $arr_parametros["clientId"];

        $this->saveLog(1, $client->cliente_id, $responseCurl, "", "curl_paypal_client_response");

        return $responseCurl;
    }
}