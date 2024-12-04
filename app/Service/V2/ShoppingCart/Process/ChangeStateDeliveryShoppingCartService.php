<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ShoppingCartRepository;
use \Illuminate\Http\Request;

class ChangeStateDeliveryShoppingCartService extends HelperPago
{

    private $shopping_cart_repository;

    public function __construct(Request $request,
        ShoppingCartRepository $shopping_cart_repository
    ) {
        parent::__construct($request);
        $this->shopping_cart_repository = $shopping_cart_repository;

    }

    public function handle($params)
    {
        try {

            $fieldValidation = $params;

            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            $newStateDelivery = $fieldValidation["newStateDelivery"];

            //Validar que exista el carrito
            $shoppingCart = $this->searchShoppingCart($id, $clientId);

            if ($shoppingCart) {

                if ($shoppingCart->estado === "pagado") {
                    $shoppingCart->estado_entrega = $newStateDelivery;

                    if ($shoppingCart->save()) {
                        $success = true;
                        $title_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                        $text_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                        $last_action = 'update_shoppingcart';
                        $data = [];
                    } else {
                        $success = false;
                        $title_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                        $text_response = CommonText::UPDATE_STATE_DELIVERY_SHOPPINGCART;
                        $last_action = 'update_shoppingcart';
                        $data = [];
                    }

                } else {
                    $success = false;
                    $title_response = 'Shoppingcart is not pay accepted';
                    $text_response = $shoppingCart->estado;
                    $last_action = 'consult_shopping_cart';
                    $data = [];
                }

            } else {
                $success = false;
                $title_response = 'Shopping cart not found';
                $text_response = 'Shopping cart not found';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error update state delivery shopping cart";
            $last_action = 'fetch data from database';
            $error =(object) $this->getErrorCheckout('E0100');
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

    public function searchShoppingCart($id, $clientId)
    {
        return $this->shopping_cart_repository->findByIdAndClient($id, $clientId);

    }
}
