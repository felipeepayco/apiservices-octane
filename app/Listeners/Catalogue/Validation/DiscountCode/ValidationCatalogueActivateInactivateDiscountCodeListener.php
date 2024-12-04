<?php
namespace App\Listeners\Catalogue\Validation\DiscountCode;

use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueActivateInactivateDiscountCodeEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Models\BblDiscountCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ValidationCatalogueActivateInactivateDiscountCodeListener extends HelperPago
{
    /**
     * ValidationCatalogueActivateInactivateDiscountCodeListener constructor.
     * @param Request $request
     */
    public $response;

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function handle(ValidationCatalogueActivateInactivateDiscountCodeEvent $event)
    {
        $validate = new Validate();
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation['clientId'];

        $fieldValidation["id"] = CommonValidation::validateIsSet($fieldValidation, 'id', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["id"], 'id', 'empty', true);

        $fieldValidation["status"] = CommonValidation::validateIsSet($fieldValidation, 'status', '', "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["status"], 'status', 'bool', true);

        if (!$validate->validateIsNumeric($fieldValidation["id"])) {

            $validate->setError(422, "id field must be an integer");

        } else {
            $id_length = floor(log10(abs($fieldValidation["id"]))) + 1;

            if ($id_length > 10) {

                $validate->setError(422, "id field can not be greater than 10 digits");

            }

            if ($fieldValidation["id"] < 0) {

                $validate->setError(422, "id field must be greater than 0");

            }
        }

        if (!$validate->validateIsNumeric($fieldValidation["id"])) {

            $discount_code = BblDiscountCode::find($fieldValidation["id"]);

            if (empty($discount_code)) {
                $validate->setError(422, "the discount code doesn't exist");

            } else {
                //VALIDATE DATE
                if ($discount_code->filtro_periodo) {

                    $current_date = Carbon::parse(Carbon::now());
                    $discount_code_expire_date = Carbon::parse($discount_code->fecha_fin);

                    if ($current_date->greaterThan($discount_code_expire_date)) {
                        $validate->setError(422, "The discount code is expired");
                    }

                }

                //VALIDATE MAX AMOUNT

                if ($discount_code->filtro_cantidad) {
                    if ($discount_code->cantidad_restante <= 0) {
                        $validate->setError(422, "The discount code can't be used anymore");

                    }
                }

            }

        }

        //VALIDATE STATUS

        if (!$validate->validateBoolValue($fieldValidation["status"])) {

            $validate->setError(422, "status field is invalid, boolean value expected");

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

            $this->saveLog(2, $clientId, '', $response, 'catalogue_activate_inactivate_discount_code');

            return $response;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }

}
