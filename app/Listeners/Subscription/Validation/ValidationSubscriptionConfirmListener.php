<?php


namespace App\Listeners\Subscription\Validation;


use App\Events\Subscription\Validation\ValidationSubscriptionConfirmEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidationSubscriptionConfirmListener extends HelperPago
{
    /**
     * ValidationSubscriptionConfirmListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @param ValidationSubscriptionConfirmEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationSubscriptionConfirmEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $arr_respuesta = [];

        $clientId = $validate->validateIsSet($data, 'clientId', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', 'empty');

        $x_cod_transaction_state = $validate->validateIsSet($data, 'x_cod_transaction_state', false);
        $x_ref_payco = $validate->validateIsSet($data, 'x_ref_payco', false);
        $x_amount = $validate->validateIsSet($data, 'x_amount', false);
        $x_amount_ok = $validate->validateIsSet($data, 'x_amount_ok', false);
        $x_id_invoice = $validate->validateIsSet($data, 'x_id_invoice', false);
        $x_transaction_id = $validate->validateIsSet($data, 'x_transaction_id', false);
        $x_currency_code = $validate->validateIsSet($data, 'x_currency_code', false);
        $x_signature = $validate->validateIsSet($data, 'x_signature', false);
        $x_cust_id_cliente = $validate->validateIsSet($data, 'x_cust_id_cliente', false);
        $x_description = $validate->validateIsSet($data, 'x_description', false);
        $x_transaction_state = $validate->validateIsSet($data, 'x_transaction_state', false);
        $x_extra1 = $validate->validateIsSet($data, 'x_extra1', false);
        $x_extra2 = $validate->validateIsSet($data, 'x_extra2', false);
        $x_extra3 = $validate->validateIsSet($data, 'x_extra3', false);
        $x_tax = $validate->validateIsSet($data, 'x_tax', false);
        $x_cardnumber = $validate->validateIsSet($data, 'x_cardnumber', false);
        $x_franchise = $validate->validateIsSet($data, 'x_franchise', false);


        CommonValidation::validateParamFormat($arr_respuesta, $validate,
        $x_cod_transaction_state, 'x_cod_transaction_state', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_ref_payco, 'x_ref_payco', 'empty');
        CommonValidation::validateParamFormat(
            $arr_respuesta, $validate, $x_transaction_state, 'x_transaction_state', 'empty'
        );
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_amount, 'x_amount', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_amount_ok, 'x_amount_ok', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_id_invoice, 'x_id_invoice', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate,
        $x_transaction_id, 'x_transaction_id', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_currency_code, 'x_currency_code', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_signature, 'x_signature', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate,
        $x_cust_id_cliente, 'x_cust_id_cliente', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_description, 'x_description', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_extra1, 'x_extra1', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_extra2, 'x_extra2', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_extra3, 'x_extra3', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_tax, 'x_tax', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_cardnumber, 'x_cardnumber', 'empty');
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $x_franchise, 'x_franchise', 'empty');

        if ($validate->totalerrors > 0 ||
        ($clientId != getenv("CLIENT_ID_APIFY_PRIVATE") && $clientId != getenv("CLIENT_ID_BABILONIA"))) {
            $success        = false;
            $last_action    = 'validation data save';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';
            $data = [];

            if ($clientId != getenv("CLIENT_ID_APIFY_PRIVATE") && $clientId != getenv("CLIENT_ID_BABILONIA")) {
                $text_response = 'unauthorized';
            } else {
                $data = [
                    'totalErrors' => $validate->totalerrors,
                    'errors'      => $validate->errorMessage,
                ];
            }

            $response = [
                'success'        => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data,
            ];

            $this->saveLog(2, $x_cust_id_cliente, $event->arr_parametros, '', 'suscripcion_babilonia_confirm');

            return $response;
        }
        $this->saveLog(1, $x_cust_id_cliente, [
            'x_ref_payco' => $x_ref_payco,
            'x_cod_transaction_state'=>$x_cod_transaction_state,
            'x_transaction_state'=>$x_transaction_state,
            'x_extra1'=>$x_extra1,
            'x_extra2'=>$x_extra2,
            'x_extra3'=>$x_extra3,
        ], '', 'suscripcion_babilonia_confirm');
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }
}