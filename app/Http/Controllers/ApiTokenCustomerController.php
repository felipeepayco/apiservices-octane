<?php

namespace App\Http\Controllers;

use App\Events\Payments\Process\ProcessCustomerEvent;
use App\Events\Payments\Process\ProcessCustomersEvent;
use App\Events\Payments\Process\ProcessCustomerUpdateEvent;
use App\Events\Payments\Process\ProcessTokenCustomerDefaultTokenCardEvent;
use App\Events\Payments\Process\ProcessTokenCustomerEvent;
use App\Events\Payments\Process\ProcessTokenCustomerNewTokenCardEvent;
use App\Events\Payments\Validation\ValidationCustomerEvent;
use App\Events\Payments\Validation\ValidationCustomersEvent;
use App\Events\Payments\Validation\ValidationCustomerUpdateEvent;
use App\Events\Payments\Validation\ValidationTokenCustomerDefaultTokenCardEvent;
use App\Events\Payments\Validation\ValidationTokenCustomerEvent;
use App\Events\Payments\Validation\ValidationTokenCustomerNewTokenCardEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiTokenCustomerController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function getTokenCustomer(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTokenCustomer = event(
                new ValidationTokenCustomerEvent($arr_parametros),
                $request);

            if (!$validationGeneralTokenCustomer[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTokenCustomer[0]);
            }

            $customerToken = event(
                new ProcessTokenCustomerEvent($validationGeneralTokenCustomer[0]),
                $request
            );
            $success = $customerToken[0]['success'];
            $title_response = $customerToken[0]['titleResponse'];
            $text_response = $customerToken[0]['textResponse'];
            $last_action = $customerToken[0]['lastAction'];
            $data = $customerToken[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function addNewTokenToCustomer(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTokenCustomer = event(
                new ValidationTokenCustomerNewTokenCardEvent($arr_parametros),
                $request);

            if (!$validationGeneralTokenCustomer[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTokenCustomer[0]);
            }

            $customerToken = event(
                new ProcessTokenCustomerNewTokenCardEvent($validationGeneralTokenCustomer[0]),
                $request
            );
            $success = $customerToken[0]['success'];
            $title_response = $customerToken[0]['titleResponse'];
            $text_response = $customerToken[0]['textResponse'];
            $last_action = $customerToken[0]['lastAction'];
            $data = $customerToken[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function getCustomer(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            if (!isset($arr_parametros["idCustomer"])) $arr_parametros["idCustomer"] = $request->get("idCustomer", "");


            $validationGeneralCustomer = event(
                new ValidationCustomerEvent($arr_parametros),
                $request);

            if (!$validationGeneralCustomer[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCustomer[0]);
            }

            $customer = event(
                new ProcessCustomerEvent($validationGeneralCustomer[0]),
                $request
            );
            $success = $customer[0]['success'];
            $title_response = $customer[0]['titleResponse'];
            $text_response = $customer[0]['textResponse'];
            $last_action = $customer[0]['lastAction'];
            $data = $customer[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function getCustomers(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();

            $validationGeneralCustomers = event(
                new ValidationCustomersEvent($arr_parametros),
                $request);

            if (!$validationGeneralCustomers[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCustomers[0]);
            }

            $customers = event(
                new ProcessCustomersEvent($validationGeneralCustomers[0]),
                $request
            );
            $success = $customers[0]['success'];
            $title_response = $customers[0]['titleResponse'];
            $text_response = $customers[0]['textResponse'];
            $last_action = $customers[0]['lastAction'];
            $data = $customers[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function updateCustomer(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();

            $validationGeneralCustomers = event(
                new ValidationCustomerUpdateEvent($arr_parametros),
                $request);

            if (!$validationGeneralCustomers[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCustomers[0]);
            }

            $customers = event(
                new ProcessCustomerUpdateEvent($validationGeneralCustomers[0]),
                $request
            );
            $success = $customers[0]['success'];
            $title_response = $customers[0]['titleResponse'];
            $text_response = $customers[0]['textResponse'];
            $last_action = $customers[0]['lastAction'];
            $data = $customers[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function addDefaultCard(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();

            $validationGeneralCustomers = event(
                new ValidationTokenCustomerDefaultTokenCardEvent($arr_parametros),
                $request);

            if (!$validationGeneralCustomers[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCustomers[0]);
            }

            $customers = event(
                new ProcessTokenCustomerDefaultTokenCardEvent($validationGeneralCustomers[0]),
                $request
            );
            $success = $customers[0]['success'];
            $title_response = $customers[0]['titleResponse'];
            $text_response = $customers[0]['textResponse'];
            $last_action = $customers[0]['lastAction'];
            $data = $customers[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }
}