<?php

namespace App\Listeners\ShoppingCart\Validation;



use App\Events\ShoppingCart\Validation\ValidationCheckoutShoppingCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Helpers\Messages\CommonText as CM;

class ValidationCheckoutShoppingCartListener extends HelperPago
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

    public function handle(ValidationCheckoutShoppingCartEvent $event)
    {
        
        $validate = new Validate();
        $data = $event->arr_parametros;
        $arr_respuesta = [];
   
        
        
        $x_fecha_transaccion = $validate->validateIsSet($data,'x_fecha_transaccion',false);
        
        $x_transaction_date = $validate->validateIsSet($data,'x_transaction_date',false);
        
        $x_cod_transaction_state = $validate->validateIsSet($data,'x_cod_transaction_state',false);
        
        $x_transaction_state = $validate->validateIsSet($data,'x_transaction_state',false);
        
        $x_ref_payco = $validate->validateIsSet($data,'x_ref_payco',false);
        
        $x_approval_code = $validate->validateIsSet($data,'x_approval_code',false);
        
        $x_bank_name = $validate->validateIsSet($data,'x_bank_name',false);

        $x_amount = $validate->validateIsSet($data,'x_amount',false);
        
        $x_customer_email = $validate->validateIsSet($data,'x_customer_email',false);
        
        $x_franchise = $validate->validateIsSet($data,'x_franchise',false);
        
        $x_id_invoice = $validate->validateIsSet($data,'x_id_invoice',false);

        $x_transaction_id = $validate->validateIsSet($data,'x_transaction_id',false);
        

        $x_currency_code = $validate->validateIsSet($data,'x_currency_code',false);
        
        $x_signature = $validate->validateIsSet($data,'x_signature',false);

        $this->validateParamFormat($arr_respuesta,$validate,$x_fecha_transaccion,'x_fecha_transaccion','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_transaction_date,'x_transaction_date','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_cod_transaction_state,'x_cod_transaction_state','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_transaction_state,'x_transaction_state','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_ref_payco,'x_ref_payco','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_approval_code,'x_approval_code','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_bank_name,'x_bank_name','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_amount,'x_amount','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_customer_email,'x_customer_email','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_franchise,'x_franchise','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_id_invoice,'x_id_invoice','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_transaction_id,'x_transaction_id','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_currency_code,'x_currency_code','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$x_signature,'x_signature','empty');
        
        if ($validate->totalerrors > 0) {

            $success         = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array(
                    'totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            $response = array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );
            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }


    private function validateParamFormat(&$arr_respuesta,$validate,$param,$paramName,$validateType,$required=true){
        if (isset($param)) {
            $vparam = true;

            if($validateType == 'empty'){
                $vparam = $validate->ValidateVacio($param, $paramName);
            }

            if (!$vparam) {
                $validate->setError(500, CM::FIELD.' '.$paramName.' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if($required){
                $validate->setError(500, CM::FIELD.' '.$paramName.' required');
            }
        }
    }
}
