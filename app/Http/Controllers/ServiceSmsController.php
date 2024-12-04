<?php

namespace App\Http\Controllers;

use App\Helpers\Pago\HelperPago;
use App\Events\Services\ServicesSmsEvent;
use App\Http\Validation\Validate as Validate;

use Illuminate\Http\Request;

class ServiceSmsController extends HelperPago
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Send a message function
     *
     * @return JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            $validator = new Validate();
            
            if (!$request->input('clientId') || !is_numeric($request->input('clientId'))) {
                $error = $validator->getErrorCheckout('A001');
                $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'recipient', "fieldType" => "text"]));
            }
            
            if (!$request->input('recipient') || !is_numeric($request->input('recipient'))) {
                $error = $validator->getErrorCheckout('A001');
                $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'recipient', "fieldType" => "text"]));
            }

            if (!$request->input('message') || !is_string($request->input('message'))) {
                $error = $validator->getErrorCheckout('A001');
                $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'message', "fieldType" => "text"]));
            }

            if ($request->has('extras') && (!$request->input('extras') || !is_array($request->input('extras')))) {
                $error = $validator->getErrorCheckout('A001');
                $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'extras', "fieldType" => "text"]));
            }
            
            if ($validator->totalerrors > 0) {
                // Response error validation
                return $this->crearRespuesta([
                    'success' => false,
                    'titleResponse' => 'Validar informacion SMS',
                    'textResponse' => 'La informacion del SMS es invalida',
                    'lastAction' => 'validate_send_message',
                    'data' => $validator->errorMessage,
                ]);
            }

            // Envio de SMS
            $services = event(new ServicesSmsEvent($request->all()));
            $response = json_decode($services[0], true);
            return $this->crearRespuesta($response);
        } catch (\Exception $exception) {
            // Exception errors
            return $this->crearRespuesta([
                'success' => false,
                'titleResponse' => 'Error enviando SMS',
                'textResponse' => 'Error enviando SMS',
                'lastAction' => 'Send Message',
                'data' => [
                    'message' => $exception->getMessage()
                ]
            ]);
        }
    }
}
