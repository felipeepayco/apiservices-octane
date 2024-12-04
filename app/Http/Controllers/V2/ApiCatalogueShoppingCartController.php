<?php

namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use App\Service\V2\ShoppingCart\Process\ChangeStateDeliveryShoppingCartService;
use App\Service\V2\ShoppingCart\Process\CheckoutConfirmationShoppingCartService;
use App\Service\V2\ShoppingCart\Process\CreateShoppingCartService;
use App\Service\V2\ShoppingCart\Process\EmptyShoppingCartService;
use App\Service\V2\ShoppingCart\Process\GetShippingInfoShoppingCartService;
use App\Service\V2\ShoppingCart\Process\GetShoppingCartService;
use App\Service\V2\ShoppingCart\Process\ListShoppingCartService;
use App\Service\V2\ShoppingCart\Process\CheckShoppingCartService;
use App\Service\V2\ShoppingCart\Process\RemoveItemShoppingCartService;
use App\Service\V2\ShoppingCart\Process\UpdateCartsShoppingCartService;

use App\Service\V2\ShoppingCart\Process\LoadPickupShoppingCartService;
use App\Service\V2\ShoppingCart\Process\SetShippingInfoShoppingCartService;
use App\Service\V2\ShoppingCart\Validations\ChangeStateDeliveryShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\CheckoutConfirmationShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\CreateShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\EmptyShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\GetShippingInfoShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\GetShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\ListShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\LoadPickupShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\SetShippingInfoShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\RemoveItemShoppingCartValidation;
use App\Service\V2\ShoppingCart\Validations\UpdateCartsShoppingCartValidation;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



class ApiCatalogueShoppingCartController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function createShoppingCart(Request $request, CreateShoppingCartValidation $createShoppingCartValidation, CreateShoppingCartService $createShoppingCartService)
    {
        try {

            
            $validationGeneral = $createShoppingCartValidation->handle($request);
    
            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
            $consult = $createShoppingCartService->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function getShoppingCart(Request $request, GetShoppingCartService $get_shopping_cart_service)
    {
        try {

            $get_shopping_cart_validation = new GetShoppingCartValidation($request);
            $validationGeneral = $get_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
            $consult = $get_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function emptyShoppingCart(Request $request, EmptyShoppingCartService $empty_shopping_cart_service)
    {
        try {

            $empty_shopping_cart_validation = new EmptyShoppingCartValidation($request);
            $validationGeneral = $empty_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $empty_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function getShippingInfo(Request $request, GetShippingInfoShoppingCartService $get_shipping_info_shopping_cart_service)
    {

        try {

            $get_shipping_info_shopping_cart_validation = new GetShippingInfoShoppingCartValidation($request);
            $validationGeneral = $get_shipping_info_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $get_shipping_info_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }
    public function setShippingInfo(Request $request, SetShippingInfoShoppingCartService $set_shipping_info_shopping_cart_service)
    {

        try {

            $set_shipping_info_shopping_cart_validation = new SetShippingInfoShoppingCartValidation($request);
            $validationGeneral = $set_shipping_info_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
            $consult = $set_shipping_info_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function listShoppingCart(Request $request, ListShoppingCartService $list_shopping_cart_service)
    {
        try {
            $list_shopping_cart_validation = new ListShoppingCartValidation($request);
            $validationGeneral = $list_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
            $consult = $list_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $data = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );

        $this->responseSpeed($data);
    }

    public function checkoutConfirmation(Request $request, CheckoutConfirmationShoppingCartService $checkout_confirmation_shopping_cart_service)
    {
        try {

            $checkout_confirmation_shopping_cart_validation = new CheckoutConfirmationShoppingCartValidation($request);
            $validationGeneral = $checkout_confirmation_shopping_cart_validation->handle($request);
            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $checkout_confirmation_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function checkShoppingCart(Request $request, CheckShoppingCartService $check_shopping_cart_service)
    {
        try {

            $consult = $check_shopping_cart_service->handle($request->all());

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function changeStateDelivery(Request $request, ChangeStateDeliveryShoppingCartService $change_status_delivery_shopping_cart_service)
    {

        try {

            $change_status_delivery_shopping_cart_validation = new ChangeStateDeliveryShoppingCartValidation($request);
            $validationGeneral = $change_status_delivery_shopping_cart_validation->handle($request);
            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $change_status_delivery_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function loadPickup(Request $request, LoadPickupShoppingCartService $load_pickup_shopping_cart_service)
    {

        try {
            $load_pickup_shopping_cart_validation = new LoadPickupShoppingCartValidation($request);
            $validationGeneral = $load_pickup_shopping_cart_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $load_pickup_shopping_cart_service->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }


    public function removeItem(Request $request, RemoveItemShoppingCartValidation $removeItemShoppingCartValidation, RemoveItemShoppingCartService $removeItemShoppingCartService)
    {

        try {

            $validationGeneral = $removeItemShoppingCartValidation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
            $consult = $removeItemShoppingCartService->handle($validationGeneral);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }

    public function updateCarts(Request $request, UpdateCartsShoppingCartValidation $updateCartsShoppingCartValidation, UpdateCartsShoppingCartService $updateCartsShoppingCartService)
    {

        try {

            $validationGeneral = $updateCartsShoppingCartValidation->handle($request);
            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            $consult = $updateCartsShoppingCartService->handle($request->all());

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }

}
