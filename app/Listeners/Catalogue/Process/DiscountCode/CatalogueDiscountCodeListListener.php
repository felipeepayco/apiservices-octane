<?php
namespace App\Listeners\Catalogue\Process\DiscountCode;

use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListEvent;
use App\Exceptions\GeneralException;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Models\BblDiscountCode;
use Illuminate\Http\Request;

class CatalogueDiscountCodeListListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
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

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueDiscountCodeListEvent $event)
    {
        try {

            $validate = new Validate();

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $pagination = CommonValidation::getFieldValidation((array) $fieldValidation, 'pagination', []);
            $page = CommonValidation::getFieldValidation((array) $pagination, 'page', 1);
            $pageSize = CommonValidation::getFieldValidation((array) $pagination, 'limit', 50);
            $consultOne = false;
            if (isset($fieldValidation["id"])) {

                if ($fieldValidation["id"] != "") {

                    if (!$validate->validateIsNumeric($fieldValidation["id"])) {

                        return $this->validateErrors("id field must be an integer");

                    } else {
                        $id_length = floor(log10(abs($fieldValidation["id"]))) + 1;

                        if ($id_length > 10) {

                            return $this->validateErrors("id field can not be greater than 10 digits");

                        }

                        if ($fieldValidation["id"] < 0) {

                            return $this->validateErrors("id field must be greater than 0");

                        }
                    }
                }

                $res = BblDiscountCode::find($fieldValidation["id"]);
                if (isset($res->id)) {
                    $consultOne = true;
                    $data = $res;
                    $success = true;
                    $titleResponse = 'Consult discount code';
                    $textResponse = 'Consult discount code successfully';
                    $lastAction = 'Consult discount code';
                } else {

                    return $this->validateErrors('Discount code not found');
                }
            } else {
                if (!$validate->validateIsNumeric($page)) {

                    return $this->validateErrors("page field must be an integer");

                } else {

                    if ($page < 1) {

                        return $this->validateErrors("page field must be greater than or equal to 1");

                    }
                }

                if (!$validate->validateIsNumeric($pageSize)) {

                    return $this->validateErrors("limit field must be an integer");

                } else {

                    if ($pageSize < 1) {

                        return $this->validateErrors("limit field must be greater than or equal to 1");

                    }
                }

                $data = BblDiscountCode::where('cliente_id', $clientId)->orderBy('id', 'DESC')->paginate($pageSize, ['*'], 'discountCode', $page);
                $success = true;
                $titleResponse = 'List Discount code successfully';
                $textResponse = 'successful consult';
                $lastAction = 'successful consult';
            }

        } catch (GeneralException $generalException) {
            $success = false;
            $titleResponse = $generalException->getMessage();
            $textResponse = $generalException->getMessage();
            $lastAction = 'generalException';
            $data = $generalException->getData();
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $titleResponse;
        $arr_respuesta['textResponse'] = $textResponse;
        $arr_respuesta['lastAction'] = $lastAction;
        $arr_respuesta['data'] = $this->mapDiscountCode($data, $consultOne);

        return $arr_respuesta;
    }

    public function validateErrors($message)
    {
        $arr_respuesta['success'] = false;
        $arr_respuesta['titleResponse'] = "error";
        $arr_respuesta['textResponse'] = $message;
        $arr_respuesta['lastAction'] = "List discount code";
        $arr_respuesta['data'] = [];

        return $arr_respuesta;
    }

    public function mapDiscountCode($data, $consultOne = false)
    {
        $result = [];
        if ($consultOne) {
            return $this->formatItem($data);
        }
        foreach ($data as $code) {
            $result[] = $this->formatItem($code);
        }
        return [
            "data" => $result,
            "currentPage" => $data->currentPage(),
            "lastPage" => $data->lastPage(), //la ultima pagina
            "nextPage" => $data->currentPage() + 1,
            "perPage" => $data->perPage(),
            "prevPage" => $data->currentPage() <= 2 ? null
            : $data->currentPage() - 1,
            "total" => $data->total(),
        ];
    }
    public function formatItem($code)
    {
        $item = [];
        if (isset($code["categorias"]) && $code["categorias"] && $code["categorias"] !== '') {
            $item["categories"] = json_decode($code["categorias"]);
        } else {
            $item["categories"] = [];
        }
        $item["id"] = $code["id"];
        $item["name"] = $code[self::NAME];
        $item["discountType"] = $code[self::DISCOUNT_TYPE];
        $item["discountAmount"] = $code[self::DISCOUNT_AMOUNT];
        $item["quantityFilter"] = $code[self::QUANTITY_FILTER];
        $item["quantity"] = $code[self::QUANTITY];
        $item["remainingQuantity"] = $code[self::REMAINING_QUANTITY];
        $item["periodFilter"] = $code[self::PERIOD_FILTER];
        $item["startDate"] = $code[self::START_DATE];
        $item["endDate"] = $code[self::END_DATE];
        $item["categoryFilter"] = $code[self::CATEGORY_FILTER];
        $item["shoppingCarFilter"] = $code[self::SHOPPING_CAR_FILTER];
        $item["shoppingCarAmount"] = $code[self::SHOPPING_CAR_AMOUNT];
        $item["combineCode"] = $code[self::COMBINE_CODE];
        $item["status"] = $code[self::STATUS];
        return $item;
    }

}
