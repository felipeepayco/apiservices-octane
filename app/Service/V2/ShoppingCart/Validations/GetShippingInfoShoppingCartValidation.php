<?php
namespace App\Service\V2\ShoppingCart\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class GetShippingInfoShoppingCartValidation extends HelperPago
{

    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    public function handle(Request $request)
    {

        $validate = new Validate();
        $data = $request->all();
        $obligatorios = ["email", "clientId", "catalogueId"];
        foreach ($obligatorios as $obligatorio) {
            if (isset($data[$obligatorio]) && $validate->ValidateVacio($data[$obligatorio], null)) {
                $arrResponse[$obligatorio] = $data[$obligatorio];
            } else {
                $validate->setError(500, "field $obligatorio required");
            }
        }

        if ($validate->totalerrors > 0) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';

            $data = ['totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage];
            $response = [
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => $data,
            ];

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;
    }
}
