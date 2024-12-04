<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Helpers\ShoppingCart\ResponseDataService;
use App\Helpers\Validation\CommonValidation;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RemoveItemShoppingCartService extends HelperPago
{

    private $productRepository;

    public function __construct(Request $request,
        ProductRepository $productRepository,
    ) {
        parent::__construct($request);

        $this->productRepository = $productRepository;

    }

    public function handle($params)
    {
        $fieldValidation = $params;
        $shoppingCart = CommonValidation::getFieldValidation($fieldValidation,"shoppingCart",null);
        $product = CommonValidation::getFieldValidation($fieldValidation,"product",null);
        $productId = CommonValidation::getFieldValidation($fieldValidation,"productId",null);
        $referenceId = CommonValidation::getFieldValidation($fieldValidation,"referenceId",null);
        $productRemove = null;
        $refIndexRemove = null;
        $success = true;
        $titleResponse = "Successful remove item shopping cart";
        $textResponse = "Successful remove item shopping cart";
        $lastAction = "Successful remove item shopping cart";

        $productsUpdateShoppingCart = [];
        $refsUpdateShoppingCart = [];
        $position = 0;
        $total = 0;
        $totalQuantity = 0;
        try {
            foreach ($shoppingCart->productos as $key => $productDataCart) {
                if ($productDataCart["id"] === $productId) {
                    $productRemove = $productDataCart;
                    if ($referenceId && count($productDataCart["referencias"]) > 1) {
                        $quantity = 0;
                        foreach ($productDataCart["referencias"] as $key => $ref) {
                            if ($ref["id"] === $referenceId) {
                                $refIndexRemove = $key;
                            } else {
                                $quantity += $ref["cantidad"];
                                $total = $total + ($ref["valor"] * $ref["cantidad"]);
                                $totalQuantity += $ref["cantidad"];
                                array_push($refsUpdateShoppingCart, $ref);
                            }
                        }
                        $productDataCart["referencias"] = $refsUpdateShoppingCart;
                        $position++;
                        $productDataCart["posicion"] = $position;
                        $productDataCart["cantidad"] = $quantity;
                        array_push($productsUpdateShoppingCart, $productDataCart);
                    }
                } else {
                    $position++;
                    $productDataCart["posicion"] = $position;
                    array_push($productsUpdateShoppingCart, $productDataCart);
                    if (isset($productDataCart["referencias"]) && count($productDataCart["referencias"]) > 0) {
                        foreach ($productDataCart["referencias"] as $key => $ref) {
                            $total = $total + ($ref["valor"] * $ref["cantidad"]);
                            $totalQuantity += $ref["cantidad"]; 
                        }
                    } else {
                        $total = $total + ($productDataCart["valor"] * $productDataCart["cantidad"]);
                        $totalQuantity += $productDataCart["cantidad"]; 
                    }
                }
            }
            $newShoppingCart = (object)$shoppingCart;
            $now = Carbon::now()->toDateTimeString();
            if (count($productsUpdateShoppingCart) !== 0) {
                $newShoppingCart->productos = $productsUpdateShoppingCart;
                $newShoppingCart->total = $total;
                $newShoppingCart->cantidad = $totalQuantity;
            } else {
                $newShoppingCart->estado = 'eliminado';
            }
            $this->resetAvailabilityProduct($productRemove, $product, $referenceId, $refIndexRemove);
            $newShoppingCart->fecha = $now;
            $newShoppingCart->save();
            $responseDataService = new ResponseDataService($this->productRepository);
            $data = $responseDataService->formateResponseData($newShoppingCart->toArray());
        } catch (\Exception $exception) {
            $success = false;

            Log::info($exception->getFile()." ".$exception->getLine()." ".$exception->getMessage());
            $titleResponse = 'Error';
            $textResponse = "Error remove item shopping cart ".$exception->getLine();
            $lastAction = 'fetch data from database '.$exception->getFile();
            $data = array('totalErrors' => 1, 'errors' => [$exception, $exception->getMessage()]);

        }

        $arrRespuesta['success'] = $success;
        $arrRespuesta['titleResponse'] = $titleResponse;
        $arrRespuesta['textResponse'] = $textResponse;
        $arrRespuesta['lastAction'] = $lastAction;
        $arrRespuesta['data'] = $data;

        return $arrRespuesta;

    }

    public function resetAvailabilityProduct($productShopingCart, $product, $referenceId, $refIndexRemove) {
        if (!isset($productShopingCart["referencias"]) || count($productShopingCart["referencias"]) === 0) {
            $product->disponible += $productShopingCart["cantidad"];
            $product->save();
        } else {
            $indexRef = $refIndexRemove === null ? 0 : $refIndexRemove;
            $newReferences = [];
            foreach ($product->referencias as $key => $ref) {
                if ($ref["id"] === $referenceId) {
                    $ref["disponible"] += $productShopingCart["referencias"][$indexRef]["cantidad"];
                    $product->disponible += $productShopingCart["referencias"][$indexRef]["cantidad"];
                }
                array_push($newReferences, $ref);
            }
            $product->referencias = $newReferences;
            $product->save();
        }
    }
}
