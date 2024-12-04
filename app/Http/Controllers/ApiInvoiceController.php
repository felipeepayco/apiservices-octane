<?php

namespace App\Http\Controllers;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;
use App\Events\Invoice\Validation\ValidationInvoiceCreateEvent;
use App\Events\Invoice\Validation\ValidationValidateAffiliationGatewayEvent;
use App\Events\Invoice\Process\ProcessInvoiceCreateEvent;
use App\Events\Invoice\Process\ProcessValidateAffiliationGatewayEvent;

class ApiInvoiceController extends HelperPago
{
    public function createInvoice(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();

            $validationInvoiceCreate = event(
                new ValidationInvoiceCreateEvent($arr_parametros),
                $request
            );
            
            if (!$validationInvoiceCreate[0]["success"]) {
                return $this->crearRespuesta($validationInvoiceCreate[0]);
            }
            
            $invoice = event(
                new ProcessInvoiceCreateEvent($validationInvoiceCreate[0]),
                $request
            );

            $success = $invoice[0]['success'];
            $title_response = $invoice[0]['titleResponse'];
            $text_response = $invoice[0]['textResponse'];
            $last_action = $invoice[0]['lastAction'];
            $data = $invoice[0]['data'];
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
        return $this->crearRespuesta($response);
    }

    public function validateAffiliationGateway(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationValidateAffiliationGateway = event(
                new ValidationValidateAffiliationGatewayEvent($arr_parametros),
                $request
            );
            if (!$validationValidateAffiliationGateway[0]["success"]) {
                return $this->crearRespuesta($validationValidateAffiliationGateway[0]);
            }
            
            $invoice = event(
                new ProcessValidateAffiliationGatewayEvent($validationValidateAffiliationGateway[0]),
                $request
            );

            $success = $invoice[0]['success'];
            $title_response = $invoice[0]['titleResponse'];
            $text_response = $invoice[0]['textResponse'];
            $last_action = $invoice[0]['lastAction'];
            $data = $invoice[0]['data'];
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
        return $this->crearRespuesta($response);
    }
}
