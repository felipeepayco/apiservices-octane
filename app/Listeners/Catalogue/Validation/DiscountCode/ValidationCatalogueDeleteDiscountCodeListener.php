<?php
namespace App\Listeners\Catalogue\Validation\DiscountCode;

use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDeleteDiscountCodeEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationCatalogueDeleteDiscountCodeListener extends HelperPago
{
    /**
     * ValidationCatalogueDeleteDiscountCodeListener constructor.
     * @param Request $request
     */

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function handle(ValidationCatalogueDeleteDiscountCodeEvent $event)
    {
        $validate = new Validate();
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation['clientId'];

        $fieldValidation["id"] = CommonValidation::validateIsSet($fieldValidation, 'id', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["id"], 'id', 'empty', true);

        if (empty($fieldValidation["id"])) {
            $validate->setError(422, "id field is invalid, the field is empty");

        }
        if (!$validate->validateIsNumeric($fieldValidation["id"])) {

            $validate->setError(422, "id field is invalid, numeric value expected");
        } else {
            $id_length = floor(log10(abs($fieldValidation["id"]))) + 1;

            if ($id_length > 10) {

                $validate->setError(422, "id field can not be greater than 10 digits");

            }

            if ($fieldValidation["id"] < 0) {

                $validate->setError(422, "id field must be greater than 0");

            }
        }

        if ($validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation data save';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';
            $data = [
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            ];

            $response = [
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            ];

            $this->saveLog(2, $clientId, '', $response, 'catalogue_delete_discount_code');

            return $response;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }

}
