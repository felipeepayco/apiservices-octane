<?php

namespace App\Http\Controllers;

use App\Events\ConsultWithdrawEvent;
use App\Events\PayPalWithdrawEvent;
use App\Events\ePaycoWithdrawEvent;
use App\Events\ValidationGeneralWithdrawPayPalEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiWithdrawalController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }


    public function getWithdraw(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();

            $consultRetiros = event(
                new ConsultWithdrawEvent($arr_parametros),
                $request
            );


            $success = $consultRetiros[0]['success'];
            $title_response = $consultRetiros[0]['titleResponse'];
            $text_response = $consultRetiros[0]['textResponse'];
            $last_action = $consultRetiros[0]['lastAction'];
            $data = $consultRetiros[0]['data'];


        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
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

    public function setWithdrawPayPal(Request $request)
    {
        try {

            $arrParams = $request->request->all();

            $validationGeneralWithdrawPayPal = event(
                new ValidationGeneralWithdrawPayPalEvent($arrParams),
                $request);
            if (!$validationGeneralWithdrawPayPal[0]["success"]) {
                return $this->crearRespuesta($validationGeneralWithdrawPayPal[0]);
            }

            $payPalWithdraw = event(
                new PayPalWithdrawEvent($arrParams),
                $request
            );


            $success = $payPalWithdraw[0]['success'];
            $title_response = $payPalWithdraw[0]['titleResponse'];
            $text_response = $payPalWithdraw[0]['textResponse'];
            $last_action = $payPalWithdraw[0]['lastAction'];
            $data = $payPalWithdraw[0]['data'];


        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
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

    public function setWithdrawePayco(Request $request)
    {
        try {

            $arrParams = $request->request->all();

            $validationGeneralWithdrawPayPal = event(
                new ValidationGeneralWithdrawPayPalEvent($arrParams),
                $request);
            if (!$validationGeneralWithdrawPayPal[0]["success"]) {
                return $this->crearRespuesta($validationGeneralWithdrawPayPal[0]);
            }

            $payPalWithdraw = event(
                new ePaycoWithdrawEvent($arrParams),
                $request
            );


            $success = $payPalWithdraw[0]['success'];
            $title_response = $payPalWithdraw[0]['titleResponse'];
            $text_response = $payPalWithdraw[0]['textResponse'];
            $last_action = $payPalWithdraw[0]['lastAction'];
            $data = $payPalWithdraw[0]['data'];


        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
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