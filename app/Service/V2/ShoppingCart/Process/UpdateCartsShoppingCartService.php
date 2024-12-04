<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Helpers\ShoppingCart\ResponseDataService;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateCartsShoppingCartService extends HelperPago
{

    private $productRepository;
    private $shoppingCartRepository;

    public function __construct(Request $request,
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository
    ) {
        parent::__construct($request);

        $this->productRepository = $productRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;

    }

    public function handle($params)
    {

        try {
            $fieldValidation = $params;
            $totalShoppingCartAmount = 0;
            $totalShoppingCartQuantity = 0;
            $updatedShoppingCartProducts = collect([]);

            $shoppingCart = $this->shoppingCartRepository->findById($fieldValidation["shoppingCartId"]);

            foreach ($fieldValidation["products"] as $product) {

                $shoppingCartProducts = collect($shoppingCart->productos);

                $productToUpdate = $shoppingCartProducts->firstWhere('id', $product["id"]);

                $originalProduct = $this->productRepository->find($product["id"]);

                if (!isset($product["references"])) {

                    //PRODUCTS WITHOUT REFERENCES

                    $this->updateShoppingCartProduct($product, $productToUpdate, $totalShoppingCartAmount, $totalShoppingCartQuantity, $shoppingCartProducts);

                    $updatedShoppingCartProducts->push($productToUpdate);

                    //UPDATING ORIGINAL PRODUCT
                    $this->updateProduct($product, $originalProduct);

                } else {
                    //PRODUCTS WITH REFERENCES

                    $this->updateShoppingCartProductWithReferences($product, $productToUpdate, $totalShoppingCartAmount, $totalShoppingCartQuantity, $shoppingCartProducts);

                    $updatedShoppingCartProducts->push($productToUpdate);

                    //UPDATING ORIGINAL PRODUCT
                    $this->updateProductWithReferences($product, $originalProduct);

                }

            }
            //UPDATING SHOPPING CART
            $shoppingCart->cantidad = $totalShoppingCartQuantity;
            $shoppingCart->total = $totalShoppingCartAmount;
            $updatedShoppingCartProducts = $updatedShoppingCartProducts->toArray();

            $shoppingCart->productos = $updatedShoppingCartProducts;

            $shoppingCart->save();

            $success = true;
            $titleResponse = "Successful update items in shopping cart";
            $textResponse = "Successful update item in sshopping cart";
            $lastAction = "Successful update items in shopping cart";

            $responseDataService = new ResponseDataService($this->productRepository);

            $shoppingCart = $this->shoppingCartRepository->findById($fieldValidation["shoppingCartId"]);

            $data = $responseDataService->formateResponseData($shoppingCart->toArray());

        } catch (\Exception $exception) {
            $success = false;

            Log::info($exception->getFile() . " " . $exception->getLine() . " " . $exception->getMessage());
            $titleResponse = 'Error';
            $textResponse = "Error updating items in shopping cart " . $exception->getLine();
            $lastAction = 'fetch data from database ' . $exception->getFile();
            $data = array('totalErrors' => 1, 'errors' => [$exception, $exception->getMessage()]);
        }

        $arrRespuesta['success'] = $success;
        $arrRespuesta['titleResponse'] = $titleResponse;
        $arrRespuesta['textResponse'] = $textResponse;
        $arrRespuesta['lastAction'] = $lastAction;
        $arrRespuesta['data'] = $data;

        return $arrRespuesta;

    }

    private function updateShoppingCartProduct($product, &$productToUpdate, &$totalShoppingCartAmount, &$totalShoppingCartQuantity)
    {

        $product_quantity = $productToUpdate["cantidad"];

        switch ($product["action"]) {
            case 'substract':

                //WHEN SUBSTRACTING IN THE SHOPPING COLLECTION CART WE HAVE TO DO THE OPPOSITE OF THE PRODUCT ONE, SINCE
                //THE PROPERTY "CANTIDAD" REPRESENTS THE TOTAL AMOUNT OF THE CART

                $productToUpdate["cantidad"] = $product_quantity + $product["finalQuantity"];
                $totalShoppingCartAmount += round($productToUpdate["valor"] * $productToUpdate["cantidad"], 2);
                $totalShoppingCartQuantity += $productToUpdate["cantidad"];
                break;

            case 'add':

                //WHEN ADDING IN THE SHOPPING CART COLLECTION WE HAVE TO DO THE OPPOSITE OF THE PRODUCT ONE, SINCE
                //THE PROPERTY "CANTIDAD" REPRESENTS THE TOTAL AMOUNT OF THE CART

                $productToUpdate["cantidad"] = $product_quantity - $product["finalQuantity"];
                $totalShoppingCartAmount += round($productToUpdate["valor"] * $productToUpdate["cantidad"], 2);
                $totalShoppingCartQuantity += $productToUpdate["cantidad"];

                break;

        }
    }

    private function updateShoppingCartProductWithReferences($product, &$productToUpdate, &$totalShoppingCartAmount, &$totalShoppingCartQuantity)
    {

        $productData = collect($product["references"]);
        $productQuantity = 0;

        foreach ($productToUpdate["referencias"] as &$refToUpdate) {

            $ref = $productData->firstWhere('id', $refToUpdate["id"]);

            if ($ref) {

                $ref_quantity = $refToUpdate["cantidad"];

                switch ($ref["action"]) {
                    case 'substract':

                        //WHEN SUBSTRACTING IN THE SHOPPING COLLECTION CART WE HAVE TO DO THE OPPOSITE OF THE PRODUCT ONE, SINCE
                        //THE PROPERTY "CANTIDAD" REPRESENTS THE TOTAL AMOUNT OF THE CART

                        $refToUpdate["cantidad"] = $ref_quantity + $ref["finalQuantity"];
                        $productQuantity += $refToUpdate["cantidad"];

                        $totalShoppingCartAmount += round($refToUpdate["valor"] * $refToUpdate["cantidad"], 2);
                        $totalShoppingCartQuantity += $refToUpdate["cantidad"];

                        break;

                    case 'add':

                        //WHEN ADDING IN THE SHOPPING CART COLLECTION WE HAVE TO DO THE OPPOSITE OF THE PRODUCT ONE, SINCE
                        //THE PROPERTY "CANTIDAD" REPRESENTS THE TOTAL AMOUNT OF THE CART

                        $refToUpdate["cantidad"] = $ref_quantity - $ref["finalQuantity"];
                        $productQuantity += $refToUpdate["cantidad"];

                        $totalShoppingCartAmount += round($refToUpdate["valor"] * $refToUpdate["cantidad"], 2);
                        $totalShoppingCartQuantity += $refToUpdate["cantidad"];

                        break;

                }

            }

        }

        $productToUpdate["cantidad"] = $productQuantity;

    }

    private function updateProduct($product, &$originalProduct)
    {

        switch ($product["action"]) {
            case 'substract':

                $originalProduct->disponible -= $product["finalQuantity"];
                break;

            case 'add':

                $originalProduct->disponible += $product["finalQuantity"];

                break;

        }

        $originalProduct->save();
    }

    private function updateProductWithReferences($product, &$originalProduct)
    {

        $productData = collect($product["references"]);

        $references = $originalProduct->referencias;
        $productAvailability = 0;
        $totalAvailabilty = 0;

        foreach ($references as &$originalRef) {
            $ref = $productData->firstWhere('id', $originalRef["id"]);

            if ($ref) {

                switch ($ref["action"]) {
                    case 'substract':

                        $originalRef["disponible"] -= $ref["finalQuantity"];

                        break;

                    case 'add':
                        $originalRef["disponible"] += $ref["finalQuantity"];

                        break;

                }

            }

            $productAvailability += $originalRef["disponible"];

        }

        $originalProduct->disponible = $productAvailability;
        $originalProduct->referencias = $references;

        $originalProduct->save();

    }

}
