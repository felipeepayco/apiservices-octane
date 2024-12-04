<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use \Illuminate\Http\Request;

class CheckShoppingCartService extends HelperPago
{

    private $shopping_cart_repository;
    private $product_repository;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shopping_cart_repository,
        ProductRepository $product_repository,

    ) {
        parent::__construct($request);
        $this->shopping_cart_repository = $shopping_cart_repository;
        $this->product_repository = $product_repository;

    }

    public function handle($params)
    {
        try {
            $fieldValidation = $params;

            $shoppingCartResult = $this->shopping_cart_repository->getByCriteria(["estado" => "activo"]);
            $data = [];
            foreach ($shoppingCartResult as $key) {

                $fecha = $key->fecha;
                $id = $key->id;

                $date = Carbon::parse($fecha);
                $now = Carbon::now();

                $hoursDifference = $date->diffInHours($now);

                if ($hoursDifference >= 24) {

                    $this->shopping_cart_repository->update(['estado' => 'abandonado'], ["id" => $id]);
                    $products = $key->productos;
                    foreach ($products as $product) {
                        $productId = $product["id"];
                        isset($product["cantidad"]) ? ($productQuantity = $product["cantidad"]) : null;

                        // query para buscar por indice producto actualizar el stock inicial de productos sin referencia
                        if (empty($product["referencias"])) {
                            $this->product_repository->incrementStock($productId, $productQuantity);
                        }

                        if (!empty($product["referencias"])) {
                            $references = $product["referencias"];
                            foreach ($references as $reference) {
                                if (isset($reference["cantidad"]) && isset($reference["id"])) {

                                    $pr = $this->product_repository->find($productId);
                                    $ref = collect($pr['referencias'])->where('id', $reference["id"])->first();

                                    $quantity = $ref["disponible"] + $reference["cantidad"];
                                    $this->product_repository->updateByCriteria(['referencias.id' => $reference["id"], 'id' => $productId], ['referencias.$.disponible' => $quantity]);
                                }
                            }
                        }
                    }
                }

            }
            $success = true;
            $title_response = "Successful updated shopping cart";
            $text_response = "successful updated shopping cart";
            $last_action = "shopping cart updated";
        } catch (\Exception $exception) {
            $success = false;

            Log::info($exception->getMessage());

            $title_response = 'Error';
            $text_response = "Error get shopping cart";
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
