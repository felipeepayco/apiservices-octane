<?php
namespace App\Listeners\Catalogue\Validation\DiscountCode;

use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueApplyDiscountCodeEvent;
use App\Events\ShoppingCart\Process\ProcessGetShoppingCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblDiscountCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Search;

class ValidationCatalogueApplyDiscountCodeListener extends HelperPago
{
    /**
     * ValidationCatalogueApplyDiscountCodeListener constructor.
     * @param Request $request
     */
    private $validate;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->validate = new Validate();

    }

    public function handle(ValidationCatalogueApplyDiscountCodeEvent $event)
    {
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation["clientId"];
        $shopping_cart_id = $fieldValidation['shopping_cart_id'];
        $catalogue_id = $fieldValidation['catalogue_id'];
        $d_code = $fieldValidation["discount_code"];
        $arr_respuesta = [];

        // VALIDATING
        $search = new Search();
        $search->setSize(1);
        $search->setFrom(0);
        $query = new MatchPhraseQuery('id', $catalogue_id);
        $search->addQuery($query);
        $catalogueResult = $this->consultElasticSearch($search->toArray(), "catalogo", false)["data"][0];

        $bbl_discount_code = BblDiscountCode::where(['nombre' => $d_code, 'cliente_id' => $catalogueResult->cliente_id,'estado' => 1])->first();

        if (empty($bbl_discount_code)) {

            $this->validate->setError(200, "Este código de descuento no se encuentra disponible");
            $v = $this->returnErrors($clientId, $bbl_discount_code);
            if (!$v["success"]) {
                return $v;
            }
        }

        $categories = collect(json_decode($bbl_discount_code->categorias))->pluck('id')->toArray();

        if (empty($bbl_discount_code)) {
            $this->validate->setError(200, "Este código de descuento no se encuentra disponible");

        }
        # Get shopping cart data
        $consult = event(
            new ProcessGetShoppingCartEvent(["clientId" => $clientId, "id" => $shopping_cart_id, "all_clients" => true]),
            []
        )[0];

        $shopping_cart_data = $consult["data"];
        $shopping_cart_products = $shopping_cart_data["products"];

        # VALIDATE AMOUNT
        if ($bbl_discount_code->filtro_carro_compra) {

            if (floatval($bbl_discount_code->monto_carro_compra) > floatval($shopping_cart_data["total"])) {
                $this->validate->setError(200, "Este código de descuento será aplicable en compras mayores a $" . floatval($bbl_discount_code->monto_carro_compra));
            }
        }
        # VALIDATE CATEGORIES
        if ($bbl_discount_code->filtro_categoria) {

            if (count($this->getCategories($shopping_cart_products, $categories))) {
                $c = collect(json_decode($bbl_discount_code->categorias))->pluck('name')->toArray();
                $allowed_categories = $string = implode(', ', $c);
                $this->validate->setError(200, "Este código de descuento solo puede ser aplicado a los productos de las categorías ({$allowed_categories}) ");

            }
        }
        # VALIDATE QUANTITY

        if ($bbl_discount_code->filtro_cantidad) {

            if ($bbl_discount_code->cantidad_restante<=0) {
                $this->validate->setError(200, "Este código de descuento no se encuentra disponible");
            }
        }

        //VALIDATE DATES

        if ($bbl_discount_code->filtro_periodo) {

            $current_date = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'))->setTime(0, 0, 0);
            $date = Carbon::createFromFormat('Y-m-d', Carbon::parse($bbl_discount_code->fecha_fin)->format('Y-m-d'))->setTime(0, 0, 0);

            if ($current_date->gt($date)) {

                $this->validate->setError(500, "Este código de descuento no se encuentra disponible");

            }

        }

        $v = $this->returnErrors($clientId, $bbl_discount_code);
        if (!$v["success"]) {
            return $v;
        }

        $arr_respuesta['success'] = true;
        $fieldValidation["bbl_discount_code_id"] = $bbl_discount_code->id;
        $fieldValidation["shopping_cart_total"] = floatval($shopping_cart_data["total"]);
        $arr_respuesta["data"] = $fieldValidation;
        return $arr_respuesta;
    }

    public function returnErrors($clientId, $bbl_discount_code)
    {
        if ($this->validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation data save';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';
            $data = [
                'totalErrors' => $this->validate->totalerrors,
                'errors' => $this->validate->errorMessage,
                'data' => $bbl_discount_code
            ];

            $response = [
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            ];

            $this->saveLog(2, $clientId, '', $response, 'catalogue_apply_discount_code');

            return $response;
        }

        $response["success"] = true;
        return $response;
    }

    private function getCategories($products, $categories)
    {

        $product_categories = array_map(function ($p) {return $p["productData"]["categories"];}, $products);
        $product_categories = array_unique(array_reduce($product_categories, 'array_merge', []));
        $not_allowed_categories = [];
        foreach ($product_categories as $ca) {

            if (!in_array($ca, $categories)) {
                array_push($not_allowed_categories, $ca);
            }
        }

        return $not_allowed_categories;
    }

}