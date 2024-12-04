<?php

namespace App\Listeners\ShoppingCart\Validation;


use App\Events\ShoppingCart\Validation\ValidationGetShippingInfoEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Catalogo;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText as CT;

class ValidationGetShippingInfoListener extends HelperPago
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

    public function handle(ValidationGetShippingInfoEvent $event)
    {

        $validate = new Validate();
        $data = $event->arr_parametros;
        $obligatorios=["email","clientId","catalogueId"];
        foreach($obligatorios as $obligatorio){
            if(isset($data[$obligatorio]) && $validate->ValidateVacio($data[$obligatorio], null)){
                $arrResponse[$obligatorio] = $data[$obligatorio];
            }else{
                $validate->setError(500, "field $obligatorio required");
            }
        }

        if ($validate->totalerrors > 0) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';

            $data =['totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage];
            $response = [
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => $data
            ];

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;
    }
}
