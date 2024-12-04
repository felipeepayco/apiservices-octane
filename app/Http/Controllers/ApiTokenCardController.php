<?php

namespace App\Http\Controllers;

use App\Events\Payments\Process\ProcessDeleteTokenCardEvent;
use App\Events\Payments\Process\ProcessTokenCardEvent;
use App\Events\Payments\Process\ProcessTokenCardV2Event;
use App\Events\Payments\Process\ProcessDeleteTokenCardV2Event;
use App\Events\Payments\Validation\ValidationDeleteTokenCardEvent;
use App\Events\Payments\Validation\ValidationDeleteTokenCardV2Event;
use App\Events\Payments\Validation\ValidationTokenCardEvent;
use App\Events\Subscription\Validation\ValidationTokenCardDefaultEvent;
use App\Events\Subscription\Process\ProcessTokenCardDefaultEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiTokenCardController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function tokenCardDefault(Request $request){

        try {
            
            $data = $request->request->all();
            $validationGeneralTokenCard = event(
                new ValidationTokenCardDefaultEvent($data),
                $request
            );
            if (!$validationGeneralTokenCard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTokenCard[0]);
            }
    
            $tokenCard = event(
                new ProcessTokenCardDefaultEvent($validationGeneralTokenCard[0]),
                $request
            );
            $success = $tokenCard[0]['success'];
            $title_response = $tokenCard[0]['titleResponse'];
            $text_response = $tokenCard[0]['textResponse'];
            $last_action = $tokenCard[0]['lastAction'];
            $data = $tokenCard[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = (object)$this->getErrorCheckout('AE100');
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

    public function tokenCardRegister(Request $request){

    try {
        
        $arr_parametros = $request->request->all();
        $arr_parametros['token']=$request->bearerToken();
        $validationGeneralTokenCard = event(
            new ValidationTokenCardEvent($arr_parametros),
            $request);
        if (!$validationGeneralTokenCard[0]["success"]) {
            return $this->crearRespuesta($validationGeneralTokenCard[0]);
        }

        $tokenCard = event(
            new ProcessTokenCardV2Event($validationGeneralTokenCard[0]),
            $request
        );
        $success = $tokenCard[0]['success'];
        $title_response = $tokenCard[0]['titleResponse'];
        $text_response = $tokenCard[0]['textResponse'];
        $last_action = $tokenCard[0]['lastAction'];
        $data = $tokenCard[0]['data'];
    } catch (\Exception $exception) {
        $success = false;
        $title_response = "Error";
        $text_response = "Error query database" . $exception->getMessage();
        $last_action = "NA";
        $error = (object)$this->getErrorCheckout('AE100');
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

    public function getTokenCard(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTokenCard = event(
                new ValidationTokenCardEvent($arr_parametros),
                $request);

            if (!$validationGeneralTokenCard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTokenCard[0]);
            }

            $tokenCard = event(
                new ProcessTokenCardEvent($validationGeneralTokenCard[0]),
                $request
            );
            $success = $tokenCard[0]['success'];
            $title_response = $tokenCard[0]['titleResponse'];
            $text_response = $tokenCard[0]['textResponse'];
            $last_action = $tokenCard[0]['lastAction'];
            $data = $tokenCard[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = (object)$this->getErrorCheckout('AE100');
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
    public function tokenCardDelete(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
           
            $validationGeneralDeleteTokenCard = event(
                new ValidationDeleteTokenCardV2Event($arr_parametros),
                $request);

            if (!$validationGeneralDeleteTokenCard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralDeleteTokenCard[0]);
            }

            $deleteTokenCard = event(
                new ProcessDeleteTokenCardV2Event($validationGeneralDeleteTokenCard[0]),
                $request
            );
            $success = $deleteTokenCard[0]['success'];
            $title_response = $deleteTokenCard[0]['titleResponse'];
            $text_response = $deleteTokenCard[0]['textResponse'];
            $last_action = $deleteTokenCard[0]['lastAction'];
            $data = $deleteTokenCard[0]['data'];
        } catch (\Exception $exception) {
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
        return $this->crearRespuesta($response);
    }
    public function deleteTokenCard(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralDeleteTokenCard = event(
                new ValidationDeleteTokenCardEvent($arr_parametros),
                $request);

            if (!$validationGeneralDeleteTokenCard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralDeleteTokenCard[0]);
            }

            $deleteTokenCard = event(
                new ProcessDeleteTokenCardEvent($validationGeneralDeleteTokenCard[0]),
                $request
            );
            $success = $deleteTokenCard[0]['success'];
            $title_response = $deleteTokenCard[0]['titleResponse'];
            $text_response = $deleteTokenCard[0]['textResponse'];
            $last_action = $deleteTokenCard[0]['lastAction'];
            $data = $deleteTokenCard[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = (object)$this->getErrorCheckout('AE100');
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