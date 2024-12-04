<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Helpers\Validation\CommonValidation;
use App\Helpers\Validation\ValidateError;
use App\Http\Validation\Validate;
use App\Models\BblDiscountCode;
use App\Repositories\V2\CatalogueRepository;
use App\Service\V2\ShoppingCart\Process\GetShoppingCartService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CatalogueApplyDiscountCodeValidation
{

    public $response;
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }

    public function validate(Request $request, GetShoppingCartService $getShoppingCartService)
    {
        $validate = new Validate();
        $this->response = $request->request->all();

        $this->response["shoppingCartId"] = CommonValidation::validateIsSet($this->response, 'shoppingCartId', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $this->response["shoppingCartId"], 'shoppingCartId', 'empty', true);

        $this->response["catalogueId"] = CommonValidation::validateIsSet($this->response, 'catalogueId', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $this->response["catalogueId"], 'catalogueId', 'empty', true);

        $this->response["discountCode"] = CommonValidation::validateIsSet($this->response, 'discountCode', null, "");
        CommonValidation::validateParamFormat($this->response, $validate, $this->response["discountCode"], 'discountCode', 'empty', true);

        $this->response["filter"]["origin"] = "epayco";

        if ($validate->validateIsNumeric($this->response["catalogueId"])) {
            $catalogueId_length = floor(log10(abs($this->response["catalogueId"]))) + 1;

            if ($catalogueId_length > 20) {

                $validate->setError(422, "catalogueId field can not be greater than 20 digits");

            }

            if ($this->response["catalogueId"] < 1) {

                $validate->setError(422, "catalogueId field must be greater than 0");

            }
        }

        $clientId = $this->response["clientId"];
        $shoppingCartId = $this->response['shoppingCartId'];
        $catalogueId = $this->response['catalogueId'];
        $dCode = $this->response["discountCode"];

        $catalogueResult = $this->catalogueRepository->find($catalogueId);
        $bblDiscountCode = (!empty($catalogueResult)) ? BblDiscountCode::where(['nombre' => $dCode, 'cliente_id' => $catalogueResult->cliente_id, 'estado' => 1])->first() : null;
       

        if (!$bblDiscountCode) {
            $validate->setError(422, "Este código de descuento no se encuentra disponible");
            $v = $this->returnErrors($validate);
            if (!$v["success"]) {
                $this->response = $v;
                return false;
            }
        }

        $categories = collect(json_decode($bblDiscountCode->categorias))->pluck('id')->toArray();

    
        $shoppingCartData = [];
        if (isset($shoppingCartId)) {
            $responseShoppingCart = $getShoppingCartService->handle(["clientId" => $clientId, "id" => $shoppingCartId, "allClients" => true]);
            $shoppingCartData = $responseShoppingCart["data"];
            $shoppingCartProducts = $shoppingCartData["products"];

            # VALIDATE AMOUNT
            if ($bblDiscountCode->filtro_carro_compra) {

                if (floatval($bblDiscountCode->monto_carro_compra) > floatval($shoppingCartData["total"])) {
                    $validate->setError(200, "Este código de descuento será aplicable en compras mayores a $" . floatval($bblDiscountCode->monto_carro_compra));
                }
            }
            # VALIDATE CATEGORIES
            if ($bblDiscountCode->filtro_categoria) {

                if (count($this->getCategories($shoppingCartProducts, $categories)) === count($shoppingCartProducts)) {
                    $c = collect(json_decode($bblDiscountCode->categorias))->pluck('name')->toArray();
                    $allowed_categories = $string = implode(', ', $c);
                    $validate->setError(200, "Este código de descuento solo puede ser aplicado a los productos de las categorías ({$allowed_categories}) ");

                }
            }
            # VALIDATE QUANTITY

            if ($bblDiscountCode->filtro_cantidad) {

                if ($bblDiscountCode->cantidad_restante <= 0) {
                    $validate->setError(200, "Este código de descuento no se encuentra disponible");
                }
            }

            //VALIDATE DATES

            if ($bblDiscountCode->filtro_periodo) {

                $current_date = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'))->setTime(0, 0, 0);
                $date = Carbon::createFromFormat('Y-m-d', Carbon::parse($bblDiscountCode->fecha_fin)->format('Y-m-d'))->setTime(0, 0, 0);

                if ($current_date->gt($date)) {

                    $validate->setError(422, "Este código de descuento no se encuentra disponible");

                }

            }

        }

        $v = $this->returnErrors($validate);
        if (!$v["success"]) {
            $this->response = $v;
            return false;
        }
        $this->response["bblDiscountCodeId"] = $bblDiscountCode->id;
        $this->response["shoppingCartTotal"] = floatval($shoppingCartData["total"]);
        return true;

    }
    public function returnErrors($validate)
    {
        $response['success'] = true;
        if ($validate->totalerrors > 0) {
            $response = ValidateError::validateError($validate);
            $response['success'] = false;
            return $response;
        }
        return $response;
    }

    private function getCategories($products, $categories)
    {
        $productCategories = array_map(function ($p) {
            return $p["productData"]["categories"];
        }, $products);
        $productCategories = array_unique(array_reduce($productCategories, 'array_merge', []));
        $notAllowedCategories = [];
        foreach ($productCategories as $ca) {
            if (!in_array($ca, $categories)) {
                array_push($notAllowedCategories, $ca);
            }
        }
        return $notAllowedCategories;
    }
}
