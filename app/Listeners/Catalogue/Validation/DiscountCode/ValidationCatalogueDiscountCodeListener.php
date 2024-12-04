<?php
namespace App\Listeners\Catalogue\Validation\DiscountCode;

use App\Common\DiscountTypeCodes;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDiscountCodeEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Models\BblDiscountCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ValidationCatalogueDiscountCodeListener extends HelperPago
{
    /**
     * ValidationCatalogueDiscountCodeListener constructor.
     * @param Request $request
     */

    const NAME = "nombre";
    const CLIENT = "cliente_id";
    public $response;

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function handle(ValidationCatalogueDiscountCodeEvent $event)
    {
        $validate = new Validate();
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation['clientId'];
        $isEdit = false;
        $max_length = 15;
        $this->response = [];

        //VALIDATE NAME
        if (isset($fieldValidation["id"])) {
            if ($fieldValidation["id"] != "") {
                $isEdit = true;

                if ($validate->validateIsNumeric($fieldValidation["id"])) {
                    $id_length = floor(log10(abs($fieldValidation["id"]))) + 1;

                    if ($id_length > 10) {
                        $validate->setError(422, "id field can not be greater than 10 digits");

                    }

                    if ($fieldValidation["id"] < 1) {
                        $validate->setError(422, "id field must be greater than 0");

                    }
                } else {
                    $validate->setError(422, "id field must be an integer");

                }

            }

        }

        $fieldValidation["name"] = CommonValidation::validateIsSet($fieldValidation, 'name', null, "");
        if (!$isEdit) {
            CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["name"], 'name', 'empty', true);
        }

        if ($fieldValidation["name"] != "" && !$isEdit) {
            if (strlen($fieldValidation["name"]) > $max_length) {
                $validate->setError(422, "name field is invalid, Maximum length is {$max_length} characters.");

            }
            if (!preg_match('/^[a-zA-Z0-9]+$/', $fieldValidation["name"])) {
                $validate->setError(422, "name field is invalid, alphanumeric value expected");
            }

            if (!$validate->validateString($fieldValidation["name"])) {
                $validate->setError(422, "name field is invalid, string value expected");
            }

            if (BblDiscountCode::where(self::NAME, $fieldValidation["name"])->where(self::CLIENT, $clientId)->first()) {
                if (!$isEdit) {
                    $validate->setError(422, "This name already exist, please use antoher one");
                }

            }
        }

        //VALIDATE DISCOUNT AMOUNT

        $fieldValidation["discountType"] = CommonValidation::validateIsSet($fieldValidation, 'discountType', null, "string");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["discountType"], 'discountType', 'empty', true);

        if (strlen($fieldValidation["discountType"]) > 50) {
            $validate->setError(422, "discountType field is invalid, Maximum length is 50 characters.");

        }

        if (!$validate->validateString($fieldValidation["discountType"])) {
            $validate->setError(422, "discountType field is invalid, string value expected");
        }

        if (!$this->validateDiscountType($fieldValidation["discountType"])) {
            $validate->setError(422, "discountType value is invalid, (" . DiscountTypeCodes::FIXED_AMOUNT . " , " . DiscountTypeCodes::PERCENTAGE . ") values were expected");

        }

        $fieldValidation["discountAmount"] = CommonValidation::validateIsSet($fieldValidation, 'discountAmount', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["discountAmount"], 'discountAmount', 'empty', true);

        if (!$validate->validateIsNumeric($fieldValidation["discountAmount"])) {

            $validate->setError(422, "discountAmount field is invalid, numeric value expected");

        } else {
            $discountAmount_length = floor(log10(abs($fieldValidation["discountAmount"]))) + 1;

            if ($discountAmount_length > 20) {
                $validate->setError(422, "discountAmount field can not be greater than 20 digits");

            }
        }

        if ($fieldValidation["discountAmount"] < 1) {

            $validate->setError(422, "discountAmount field can not be negative or less than 1");

        }

        if($fieldValidation["discountType"]=="porcentaje")
        {
            if($fieldValidation["discountAmount"]>90)
            {
                $validate->setError(422, "discountAmount field must be equal to or less than 90");
     
            }
        }

        //VALIDATE QUANTITY

        $fieldValidation["quantityFilter"] = CommonValidation::validateIsSet($fieldValidation, 'quantityFilter', false, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["quantityFilter"], 'quantityFilter', 'bool', false);

        if (!$validate->validateBoolValue($fieldValidation["quantityFilter"])) {
            $validate->setError(422, "quantityFilter field is invalid, boolean value expected");
        }

        if ($fieldValidation["quantityFilter"]) {

            if ($fieldValidation["quantity"] == "") {
                $validate->setError(422, "quantity field is required");

            }

            if (!$validate->validateIsNumeric($fieldValidation["quantity"])) {

                $validate->setError(422, "quantity field is invalid, numeric value expected");

            } else {
                $quantity_length = floor(log10(abs($fieldValidation["quantity"]))) + 1;

                if ($quantity_length > 10) {
                    $validate->setError(422, "quantity field can not be greater than 10 digits");

                }
            }

            if ($fieldValidation["quantity"] <= 0) {

                $validate->setError(422, "quantity field cannot be less than or equal to 0");

            }
        }

        //VALIDATE DATES

        $fieldValidation["periodFilter"] = CommonValidation::validateIsSet($fieldValidation, 'periodFilter', false, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["periodFilter"], 'periodFilter', 'bool', false);

        if ($validate->validateBoolValue($fieldValidation["periodFilter"])) {

            if ($fieldValidation["periodFilter"]) {

                $fieldValidation["startDate"] = CommonValidation::validateIsSet($fieldValidation, 'startDate', null, "");
                CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["startDate"], 'startDate', 'empty', true);

                $fieldValidation["endDate"] = CommonValidation::validateIsSet($fieldValidation, 'endDate', null, "string");
                CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["endDate"], 'endDate', 'empty', true);

                if ($validate->validateDateWithCarbon($fieldValidation["startDate"]) && $validate->validateDateWithCarbon($fieldValidation["endDate"])) {

                    $current_date = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'))->setTime(0, 0, 0);
                    $startDate = Carbon::createFromFormat('Y-m-d', Carbon::parse($fieldValidation["startDate"])->format('Y-m-d'))->setTime(0, 0, 0);
                    $endDate = Carbon::createFromFormat('Y-m-d', Carbon::parse($fieldValidation["endDate"])->format('Y-m-d'))->setTime(0, 0, 0);

                    if (($current_date->gt($startDate)) || ($current_date->gt($endDate))) {

                        $validate->setError(422, "Current date can't be greater than the selected dates");

                    }

                    if ($endDate->lt($startDate)) {
                        $validate->setError(422, "End date can't be less than the start date");

                    }

                } else {
                    $validate->setError(422, "Invalid dates");

                }
            }

        } else {
            $validate->setError(422, "periodFilter field is invalid, boolean value expected");

        }

        //VALIDATE CATEGORIES

        $fieldValidation["categoryFilter"] = CommonValidation::validateIsSet($fieldValidation, 'categoryFilter', false, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["categoryFilter"], 'categoryFilter', 'bool', false);

        if ($validate->validateBoolValue($fieldValidation["categoryFilter"])) {

            if ($fieldValidation["categoryFilter"]) {

                $fieldValidation["categories"] = CommonValidation::validateIsSet($fieldValidation, 'categories', null);
                CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["categories"], 'categories', 'empty', true);

                if (!is_array($fieldValidation["categories"])) {

                    $validate->setError(422, "categories field is invalid, array expected");

                }

                if (empty($fieldValidation["categories"])) {

                    $validate->setError(422, "categories field is empty");

                }

            }

        } else {
            $validate->setError(422, "categoryFilter field is invalid, boolean value expected");

        }

        //VALIDATE SHOPPING CART
        $fieldValidation["shoppingCarFilter"] = CommonValidation::validateIsSet($fieldValidation, 'shoppingCarFilter', false, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["shoppingCarFilter"], 'shoppingCarFilter', 'bool', false);

        if ($validate->validateBoolValue($fieldValidation["shoppingCarFilter"])) {

            if ($fieldValidation["shoppingCarFilter"]) {

                $fieldValidation["shoppingCarAmount"] = CommonValidation::validateIsSet($fieldValidation, 'shoppingCarAmount', null, "");
                CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["shoppingCarAmount"], 'shoppingCarAmount', 'empty', true);

                if ($fieldValidation["shoppingCarAmount"] < 0) {
                    $validate->setError(422, "shoppingCarAmount must be greater than or equal to 0");

                }

                if (!$validate->validateIsNumeric($fieldValidation["shoppingCarAmount"])) {

                    $validate->setError(422, "shoppingCarAmount field is invalid, numeric value expected");

                } else {
                    $shopping_cart_length = floor(log10(abs($fieldValidation["shoppingCarAmount"]))) + 1;

                    if ($shopping_cart_length > 10) {
                        $validate->setError(422, "shoppingCarAmount field can not be greater than 10 digits");

                    }
                }
            }
        } else {
            $validate->setError(422, "shoppingCarFilter field is invalid, boolean value expected");

        }

        //VALIDATE COMBINE CODE
        $fieldValidation["combineCode"] = CommonValidation::validateIsSet($fieldValidation, 'combineCode', false, "");
        CommonValidation::validateParamFormat($this->response, $validate, $fieldValidation["combineCode"], 'combineCode', 'bool', false);

        if (!$validate->validateBoolValue($fieldValidation["combineCode"])) {

            $validate->setError(422, "combineCode field is invalid, boolean value expected");

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

            $this->saveLog(2, $clientId, '', $response, 'catalogue_discount_code');

            return $response;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }

    private function validateDiscountType($value): bool
    {
        switch (strtolower($value)) {
            case DiscountTypeCodes::FIXED_AMOUNT:
                return true;
                break;

            case DiscountTypeCodes::PERCENTAGE:
                return true;
                break;

            default:
                return false;
                break;
        }
    }
}
