<?php

namespace App\Listeners;

use App\Events\ValidationGeneralWithdrawPayPalEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationWithdrawPayPalListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ValidationGeneralWithdrawPayPalEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $clientId = $data["clientId"];
        $arrResponse["clientId"] = $clientId;


        if (isset($data["total"]) && $data["total"] != "") {
            if (is_numeric($data["total"]) || is_float($data["total"])) {
                $withdrawalTotal = (float)$data["total"];
                $arrResponse['total'] = (float)$withdrawalTotal;
            } else {
                $validate->setError(500, "field total is number type");
            }

        } else {
            $validate->setError(500, "field total is required");
        }

        if (isset($data["bankAccountId"]) && $data["bankAccountId"] != "") {
            if (is_int($data["bankAccountId"])) {
                $bankAccountId = $data["bankAccountId"];
                $arrResponse['bankAccountId'] = $bankAccountId;
            } else {
                $validate->setError(500, "field bankAccountId is integer type");
            }

        } else {
            $validate->setError(500, "field bankAccountId is required");
        }


        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'title_response' => $title_response,
                'text_response' => $text_response,
                'last_action' => $last_action,
                'data' => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response, 'set_withdraw_paypal');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;
    }
}