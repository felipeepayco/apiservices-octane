<?php

namespace App\Listeners\Buttons\Validation;


use App\Events\Buttons\Validation\ValidationGeneralSellNewButtonEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralSellNewButtonListener extends HelperPago
{
    /**
     * Handle the event.
     * @return void
     */
    public function handle(ValidationGeneralSellNewButtonEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $clientId = $validate->validateIsSet($data,'clientId',false);
        $idButton = $validate->validateIsSet($data, 'id',0);
        $currency = $validate->validateIsSet($data, 'currency',false);
        $value = $validate->validateIsSet($data,'amount',false);
        $reference = $validate->validateIsSet($data,'reference','');
        $type = $validate->validateIsSet($data,'type',false);
        $amountBase = $validate->validateIsSet($data,'amountBase','');
        $urlConfirmation = $validate->validateIsSet($data,'urlConfirmation','');
        $urlResponse = $validate->validateIsSet($data,'urlResponse','');
        $tax = $validate->validateIsSet($data,'tax',0);
        $urlImage = $validate->validateIsSet($data,'urlImage','');
        $urlImageexternal = $validate->validateIsSet($data,'urlImageexternal','');
        $ico = $validate->validateIsSet($data,'icoTax',0);
        $detail = $validate->validateIsSet($data,'detail','0', 'string');
        $description = $validate->validateIsSet($data,'description','0', 'string');

        if (!$currency) {
            $validate->setError(500, __("error.field required", ["field" => "currency"]));
        } else {
            $arr_respuesta['currency'] = $currency;
        }
        $arr_respuesta["reference"]=$reference;
        $arr_respuesta["type"]=$type;
        $arr_respuesta["amountBase"]=$amountBase;
        $arr_respuesta["urlConfirmation"] = $urlConfirmation;
        $arr_respuesta["urlResponse"] = $urlResponse;
        $arr_respuesta["urlImage"]=$urlImage;
        $arr_respuesta["urlImageexternal"]=$urlImageexternal;
        if ($tax != 0 || $ico != 0) {
            $tax = $value * ($tax / 100);
            $ico = $value * ($ico / 100);
            $base = $value;
            $arr_respuesta["base"] = $base;
            $value = $value + $tax + $ico;
        }
        $arr_respuesta['tax'] = $tax;
        $arr_respuesta['icoTax'] = $ico;
        $arr_respuesta["detail"]=$detail;
        $arr_respuesta['id'] = $idButton;

        if (!$clientId) {
            $validate->setError(500, __("error.field required", ["field" => "clientId"]));
        } else {
            $arr_respuesta['clientId'] = $clientId;
        }

        if (!$value) {
            $validate->setError(500, __("error.field required", ["field" => "amount"]));
        } else {
            $arr_respuesta['amount'] = $value;
        }

        if (!$description) {
            $validate->setError(500, __("error.field required", ["field" => "description"]));
        } else {
            $arr_respuesta['description'] = $description;
        }

        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'title_response' => $title_response,
                'text_response' => $text_response,
                'last_action' => $last_action,
                'data' => $data
            );
            $this->saveLog(2,$clientId, '', $response, 'consult_sell_list');

            return $response;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }
}