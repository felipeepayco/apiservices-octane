<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;

class EmptyShoppingCartService extends HelperPago
{
    private $product_repository;
    private $shopping_cart_repository;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shopping_cart_repository,
        ProductRepository $product_repository
    ) {
        parent::__construct($request);
        $this->product_repository = $product_repository;
        $this->shopping_cart_repository = $shopping_cart_repository;

    }

    public function handle($params)
    {
        try {

            $fieldValidation = $params;

            $id = (string) isset($fieldValidation["id"]) ? $fieldValidation["id"] : 0;
            $clientId = isset($fieldValidation["clientId"]) ? $fieldValidation["clientId"] : 0;

            if (trim($id) != '' && $clientId != 0) {

                $invoices = $this->shopping_cart_repository->getByIdAndClient($id, $clientId);

                if ($invoices->count() > 0) {

                    foreach ($invoices as $invoice) {
                        foreach ($invoice["productos"] as $product) {
                            $productId = $product["id"];

                            if (!isset($product["referencias"])) {

                                $relatedProduct = $this->product_repository->findByClientAndStatus($productId, $clientId, 1);

                                if ($relatedProduct) {
                                    $quantity = $product->cantidad ?? null;
                                    $relatedProduct->disponible += $quantity;
                                    $relatedProduct->save();
                                }

                                if ($relatedProduct && $relatedProduct->wasChanged('disponible')) {
                                    $categoryUpdated = true;
                                } else {
                                    $categoryUpdated = false;
                                }

                            } else {

                                if (count($product["referencias"]) > 0) {

                                    foreach ($product["referencias"] as $referencia) {
                                        $productId = $referencia["id"];
                                        $quantity = $referencia["cantidad"] ?? null;

                                        $relatedProduct = $this->product_repository->findByClientAndStatus($productId, $clientId, 1);

                                        if ($relatedProduct) {

                                            if (!isset($relatedProduct["referencias"])) {
                                                foreach ($relatedProduct["referencias"] as &$ref) {
                                                    if ($ref['id'] == $productId) {
                                                        $ref['disponible'] += $quantity;
                                                    }
                                                }
                                                $relatedProduct->save();

                                            }
                                        }

                                    }
                                }
                            }

                        }

                        $invoice->estado = 'eliminado';
                        $updateState = $invoice->save();

                    }

                    if ($updateState) {
                        $success = true;
                        $title_response = 'Successful cart emptied';
                        $text_response = 'successful cart emptied';
                        $last_action = 'cart emptied';
                        $data = [];
                    } else {
                        $success = false;
                        $title_response = 'Error empty cart';
                        $text_response = 'Error empty cart';
                        $last_action = 'delete sell';
                        $data = [];
                    }

                } else {
                    $success = false;
                    $title_response = 'ID not found';
                    $text_response = "Error finding shopping cart";
                    $last_action = 'fetch data from database';
                    $error = (object) $this->getErrorCheckout('E0100');
                    $validate = new Validate();
                    $validate->setError($error->error_code, $error->error_message);
                    $data = [];
                }

            } else {
                $success = false;
                $title_response = 'ID not found';
                $text_response = "Error finding shopping cart";
                $last_action = 'fetch data from database';
                $error =(object) $this->getErrorCheckout('E0100');
                $validate = new Validate();
                $validate->setError($error->error_code, $error->error_message);
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'ID not found';
            $text_response = "Error finding shopping cart";
            $last_action = 'fetch data from database';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}
