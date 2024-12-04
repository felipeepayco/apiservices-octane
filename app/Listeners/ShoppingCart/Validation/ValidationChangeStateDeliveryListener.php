<?php

namespace App\Listeners\ShoppingCart\Validation;


use App\Events\ShoppingCart\Validation\ValidationChangeStateDeliveryEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Catalogo;
use App\Helpers\Messages\CommonText;
use App\Helpers\Validation\CommonValidation;

class ValidationChangeStateDeliveryListener extends HelperPago
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

    public function handle(ValidationChangeStateDeliveryEvent $event)
    {

        $validate = new Validate();
        $data = $event->arr_parametros;

        $clientId = CommonValidation::validateIsSet($data,'clientId',null,'int');
        $id = CommonValidation::validateIsSet($data,'id',null,'string');
        $filter = CommonValidation::validateIsSet($data,'filter',null,'object');
        $newStateDelivery = CommonValidation::validateIsSet($data,'newStateDelivery',null,'string');

        CommonValidation::validateParamFormat($arr_respuesta,$validate,$clientId,'clientId',CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$id,'id',CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$newStateDelivery,'newStateDelivery',CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$filter,'filter',CommonText::EMPTY, false);

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
            return array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}
