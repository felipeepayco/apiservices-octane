<?php
namespace App\Service\V2\ShoppingCart\Validations;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ChangeStateDeliveryShoppingCartValidation extends HelperPago
{

    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    public function handle(Request $request)
    {

        $validate = new Validate();
        $data = $request->all();

        $clientId = CommonValidation::validateIsSet($data, 'clientId', null, 'int');
        $id = CommonValidation::validateIsSet($data, 'id', null, 'string');
        $filter = CommonValidation::validateIsSet($data, 'filter', null, 'object');
        $newStateDelivery = CommonValidation::validateIsSet($data, 'newStateDelivery', null, 'string');

        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $id, 'id', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $newStateDelivery, 'newStateDelivery', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $filter, 'filter', CommonText::EMPTY, false);

        if ($validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
            array(
                'totalerrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
            return array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            );
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}
