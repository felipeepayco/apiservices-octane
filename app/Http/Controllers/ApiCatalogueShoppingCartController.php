<?php

namespace App\Http\Controllers;

use App\Events\ShoppingCart\Process\CheckEmptyCartEvent;
use App\Events\ShoppingCart\Process\CheckShoppingCartEvent;
use App\Events\ShoppingCart\Process\EmptyCartEvent;
use App\Events\ShoppingCart\Process\GetShippingInfoEvent;
use App\Events\ShoppingCart\Process\ProcessChangeStateDeliveryEvent;
use App\Events\ShoppingCart\Process\ProcessCheckoutConfirmationEvent;
use App\Events\ShoppingCart\Process\ProcessCreateShoppingCartEvent;
use App\Events\ShoppingCart\Process\ProcessGetShoppingCartEvent;
use App\Events\ShoppingCart\Process\ProcessListShoppingCartEvent;
use App\Events\ShoppingCart\Process\ProcessLoadPickupEvent;
use App\Events\ShoppingCart\Process\ProcessPaymentReceiptEvent;
use App\Events\ShoppingCart\Process\SetShippingInfoEvent;
use App\Events\ShoppingCart\Validation\ValidationChangeStateDeliveryEvent;
use App\Events\ShoppingCart\Validation\ValidationCheckEmptyCartEvent;
use App\Events\ShoppingCart\Validation\ValidationCheckoutShoppingCartEvent;
use App\Events\ShoppingCart\Validation\ValidationCreateShoppingCartEvent;
use App\Events\ShoppingCart\Validation\ValidationEmptyCartEvent;
use App\Events\ShoppingCart\Validation\ValidationGetShippingInfoEvent;
use App\Events\ShoppingCart\Validation\ValidationGetShoppingCartEvent;
use App\Events\ShoppingCart\Validation\ValidationListShoppingCartEvent;
use App\Events\ShoppingCart\Validation\ValidationLoadPickupEvent;
use App\Events\ShoppingCart\Validation\ValidationPaymentReceiptEvent;
use App\Events\ShoppingCart\Validation\ValidationSetShippingInfoEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCatalogueShoppingCartController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function createShoppingCart(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationCreateShoppingCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessCreateShoppingCartEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function checkShoppingCart(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();

            $consult = event(
                new CheckShoppingCartEvent($arr_parametros),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function checkEmptyCart(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationCheckEmptyCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new CheckEmptyCartEvent($validationGeneral[0]),
                $request
            );

            if ($consult[0]['success'] !== "success") {
                return response()->json(
                    $consult,
                    400
                );
            }
            return response()->json(
                $consult,
                200
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function getShoppingCart(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();

            $validationGeneral = event(
                new ValidationGetShoppingCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessGetShoppingCartEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function emptyShoppingCart(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationEmptyCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new EmptyCartEvent($validationGeneral[0]),
                $request
            );

            if ($consult[0]['success'] !== "success") {
                return response()->json(
                    $consult,
                    400
                );
            }
            return response()->json(
                $consult,
                200
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function getShippingInfo(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationGetShippingInfoEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new GetShippingInfoEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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
    public function setShippingInfo(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationSetShippingInfoEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new SetShippingInfoEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function listShoppingCart(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationListShoppingCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessListShoppingCartEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function checkoutConfirmation(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();

            $validationGeneral = event(
                new ValidationCheckoutShoppingCartEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $processCheckout = event(
                new ProcessCheckoutConfirmationEvent($validationGeneral[0]),
                $request
            );

            $success = $processCheckout[0]['success'];
            $title_response = $processCheckout[0]['titleResponse'];
            $text_response = $processCheckout[0]['textResponse'];
            $last_action = $processCheckout[0]['lastAction'];
            $data = $processCheckout[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function paymentReceipt(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationPaymentReceiptEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessPaymentReceiptEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function changeStateDelivery(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationChangeStateDeliveryEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessChangeStateDeliveryEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function loadPickup(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationLoadPickupEvent($arr_parametros),
                $request
            );

            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $consult = event(
                new ProcessLoadPickupEvent($validationGeneral[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];
        } catch (Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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
