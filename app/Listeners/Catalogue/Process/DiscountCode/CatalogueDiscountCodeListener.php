<?php
namespace App\Listeners\Catalogue\Process\DiscountCode;

use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeEvent;
use App\Exceptions\GeneralException;
use App\Models\BblDiscountCode;
use Illuminate\Http\Request;

class CatalogueDiscountCodeListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */

    private $discount_code;

    const NAME = "nombre";
    const DISCOUNT_TYPE = "tipo_descuento";
    const DISCOUNT_AMOUNT = "monto_descuento";
    const QUANTITY_FILTER = "filtro_cantidad";
    const QUANTITY = "cantidad";
    const REMAINING_QUANTITY = "cantidad_restante";
    const PERIOD_FILTER = "filtro_periodo";
    const START_DATE = "fecha_inicio";
    const END_DATE = "fecha_fin";
    const CATEGORY_FILTER = "filtro_categoria";
    const CATEGORY = "categorias";
    const SHOPPING_CAR_FILTER = "filtro_carro_compra";
    const SHOPPING_CAR_AMOUNT = "monto_carro_compra";
    const STATUS = "estado";
    const COMBINE_CODE = "combinar_codigo";
    const CLIENT_ID = "cliente_id";

    public function __construct(Request $request, BblDiscountCode $discount_code)
    {

        $this->discount_code = $discount_code;

    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueDiscountCodeEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            if (isset($fieldValidation["id"])) {

                $res = $this->discount_code->find($fieldValidation["id"]);
                if (isset($res->id)) {
                    $this->discount_code = $res;
                } else {
                    $arr_respuesta['success'] = false;
                    $arr_respuesta['titleResponse'] = "error";
                    $arr_respuesta['textResponse'] = "The discount code doesn't exist";
                    $arr_respuesta['lastAction'] = "edit discount code";
                    $arr_respuesta['data'] = [];

                    return $arr_respuesta;
                }
            } else {
                $this->discount_code[self::NAME] = $fieldValidation["name"];
            }

            $this->discount_code[self::CLIENT_ID] = $fieldValidation["clientId"];

            $this->discount_code[self::DISCOUNT_TYPE] = strtolower($fieldValidation["discountType"]);
            $this->discount_code[self::DISCOUNT_AMOUNT] = $fieldValidation["discountAmount"];

            //DEFAULT VALUES IS FALSE
            $this->discount_code[self::QUANTITY_FILTER] = $fieldValidation["quantityFilter"];

            if ($fieldValidation["quantityFilter"]) {

                if (isset($fieldValidation["id"]) && $fieldValidation["id"] != "") {

                    $quantity = $fieldValidation["quantity"];
                    $remainingQuantity = $this->discount_code[self::REMAINING_QUANTITY];

                    if (($quantity <= $remainingQuantity) || ($quantity == $remainingQuantity) || ($this->discount_code[self::QUANTITY] == $this->discount_code[self::REMAINING_QUANTITY])) {

                        $this->discount_code[self::QUANTITY] = $fieldValidation["quantity"];
                        $this->discount_code[self::REMAINING_QUANTITY] = $fieldValidation["quantity"];

                    } else {

                        $oldRemainingQuantity = $this->discount_code[self::QUANTITY] - $this->discount_code[self::REMAINING_QUANTITY];
                        $newRemainingQuantity = $fieldValidation["quantity"] - $oldRemainingQuantity;
                        $this->discount_code[self::QUANTITY] = $fieldValidation["quantity"];
                        $this->discount_code[self::REMAINING_QUANTITY] = $newRemainingQuantity;

                    }

                } else {
                    $this->discount_code[self::QUANTITY] = $fieldValidation["quantity"];
                    $this->discount_code[self::REMAINING_QUANTITY] = $fieldValidation["quantity"];
                }

            }

            //DEFAULT VALUES IS FALSE
            $this->discount_code[self::PERIOD_FILTER] = $fieldValidation["periodFilter"];

            if ($fieldValidation["periodFilter"]) {
                $this->discount_code[self::START_DATE] = $fieldValidation["startDate"];
                $this->discount_code[self::END_DATE] = $fieldValidation["endDate"];
            }

            //DEFAULT VALUES IS FALSE
            $this->discount_code[self::CATEGORY_FILTER] = $fieldValidation["categoryFilter"];

            if ($fieldValidation["categoryFilter"]) {
                $categories = json_encode($fieldValidation["categories"]);
                $this->discount_code[self::CATEGORY] = $categories;

            }

            //DEFAULT VALUES IS FALSE
            $this->discount_code[self::SHOPPING_CAR_FILTER] = $fieldValidation["shoppingCarFilter"];

            if ($fieldValidation["shoppingCarFilter"]) {
                $this->discount_code[self::SHOPPING_CAR_AMOUNT] = $fieldValidation["shoppingCarAmount"];

            }

            //STORE DATA

            $this->discount_code[self::COMBINE_CODE] = $fieldValidation["combineCode"];

            //DEFAULT VALUE IS TRUE
            $this->discount_code[self::STATUS] = true;

            $data = $this->discount_code->save();

            if ($data) {

                $success = true;

                $title_response = isset($fieldValidation["id"]) ? 'Discount code updated successfully' : 'Discount code added successfully';
                $text_response = 'successful consult';
                $last_action = 'successful consult';
                $data = $data;
            } else {
                $success = false;
                $title_response = 'An error has ocurred, please try again';
                $text_response = 'An error has ocurred';
                $last_action = 'An error has ocurred';
                $data = $data;
            }

        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
