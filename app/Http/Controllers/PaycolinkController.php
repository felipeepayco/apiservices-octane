<?php
namespace App\Http\Controllers;

use App\Events\PaycoLink\Validation\ValidationPaycoLinkPaymentCreateEvent;
use App\Events\PaycoLink\Process\ProcessPaycoLinkPaymentCreateEvent;
use App\Helpers\Pago\HelperPago;
use Illuminate\Http\Request;
use App\Http\Validation\Validate;
use Exception;

class PaycolinkController extends HelperPago
{
    public $logSession;
    public function create(Request $request)
    {
        try{
        
            $arr_parametros = $request->request->all();
            $this->logSession = $this->optionalSaveLog('request', $arr_parametros, $arr_parametros['clientId'],'paycolink_create_payment');
            $arr_parametros['log_session'] = $this->logSession;

            $validationPaycoLinkPaymentCreateEvent= event( 
                new ValidationPaycoLinkPaymentCreateEvent($arr_parametros) 
            );

            if (!$validationPaycoLinkPaymentCreateEvent[0]["success"]) {
                return $this->crearRespuesta($validationPaycoLinkPaymentCreateEvent[0]);
            }
            $paycoLinkPaymentCreate = event( 
                new ProcessPaycoLinkPaymentCreateEvent($validationPaycoLinkPaymentCreateEvent[0]) 
            );

            $success        = $paycoLinkPaymentCreate[0]['success'];
            $title_response = $paycoLinkPaymentCreate[0]['titleResponse'];
            $text_response  = $paycoLinkPaymentCreate[0]['textResponse'];
            $last_action    = $paycoLinkPaymentCreate[0]['lastAction'];
            $data           = $paycoLinkPaymentCreate[0]['data'];

        } catch (Exception $exception) {

            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
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

        $this->optionalSaveLog('response', $response, $arr_parametros['clientId'],'paycolink_create_payment', $this->logSession);
        return $this->crearRespuesta($response);
    }

}
