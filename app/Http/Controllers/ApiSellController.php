<?php

namespace App\Http\Controllers;

use App\Events\Buttons\Process\ConsultSellNewButtonEvent;
use App\Events\Buttons\Validation\ValidationGeneralSellNewButtonEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonListEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonShowEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonDeleteEvent;
use App\Events\ValidationGeneralKeyShowEvent;
use App\Events\Buttons\Process\ConsultButtonShowEvent;
use App\Events\Buttons\Process\ConsultButtonListEvent;
use App\Events\Buttons\Process\ConsultButtonDeleteEvent;
use App\Events\ConsultKeyShowEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiSellController extends HelperPago
{

 

    public function sellUpdate(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellUpdate = event(new ValidationGeneralSellNewEvent($arr_parametros),
                $request);

            if (!$validationGeneralSellUpdate[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSellUpdate[0]);
            }

            $consultSellUpdate = event(
                new ConsultSellNewEvent($validationGeneralSellUpdate[0], $request),
                $request
            );

            $success = $consultSellUpdate[0]['success'];
            $title_response = $consultSellUpdate[0]['titleResponse'];
            $text_response = $consultSellUpdate[0]['textResponse'];
            $last_action = $consultSellUpdate[0]['lastAction'];
            $data = $consultSellUpdate[0]['data'];

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


    public function sellNewButton(Request $request)
    {
        try {
        $arr_parametros = $request->request->all();
        $validationGeneralSellNew = event(new ValidationGeneralSellNewButtonEvent($arr_parametros), $request);

        if (!$validationGeneralSellNew[0]["success"]) {
            return $this->crearRespuesta($validationGeneralSellNew[0]);
        }

        $consultSellNewButton = event(
            new ConsultSellNewButtonEvent($validationGeneralSellNew[0],$request),
            $request
        );

            $success = $consultSellNewButton[0]['success'];
            $title_response = $consultSellNewButton[0]['titleResponse'];
            $text_response = $consultSellNewButton[0]['textResponse'];
            $last_action = $consultSellNewButton[0]['lastAction'];
            $data = $consultSellNewButton[0]['data'];

        } catch (Exception $exception) {
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

    public function buttonList (Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralButtonListEvent($arr_parametros),
                $request);

            if (!$validationGeneralSellList[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList = event(
                new ConsultButtonListEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $key = $consultSellList[0]['key'];
            $data = $consultSellList[0]['data'];
            $types = array(
                '1' => 'Email',
                '2' => 'Link',
                '3' => 'SMS',
                '4' => 'Social Network',
            );
        } catch (Exception $exception) {
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
            $types = [];
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'key' => $key,
            'data' => $data,
            'types' => $types
        );
        return $this->crearRespuesta($response);
    }

    public function buttonShow (Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralButtonShowEvent($arr_parametros),
                $request);

            if (!$validationGeneralSellList[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList = event(
                new ConsultButtonShowEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
        } catch (Exception $exception) {
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
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function buttonDelete (Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellDelete = event(
                new ValidationGeneralButtonDeleteEvent($arr_parametros),
                $request);
            if (!$validationGeneralSellDelete[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSellDelete[0]);
            }

            $consultButtonDelete = event(
                new ConsultButtonDeleteEvent($validationGeneralSellDelete[0]),
                $request
            );

            $success = $consultButtonDelete[0]['success'];
            $title_response = $consultButtonDelete[0]['titleResponse'];
            $text_response = $consultButtonDelete[0]['textResponse'];
            $last_action = $consultButtonDelete[0]['lastAction'];
            $data = $consultButtonDelete[0]['data'];


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

    public function getLlavesCliente (Request $request){
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralKeyShowEvent($arr_parametros),
                $request);

            if (!$validationGeneralSellList[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList = event(
                new ConsultKeyShowEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
            $types = array(
                '1' => 'Email',
                '2' => 'Link',
                '3' => 'SMS',
                '4' => 'Social Network',
            );
        } catch (Exception $exception) {
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
            $types = [];
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
            'types' => $types
        );
        return $this->crearRespuesta($response);
    }
}