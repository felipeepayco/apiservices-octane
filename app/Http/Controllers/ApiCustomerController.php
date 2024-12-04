<?php

namespace App\Http\Controllers;

use App\Events\Customer\Process\CustomerNewEvent;
use App\Events\Customer\Validation\ValidationCustomerNewEvent;

use App\Events\Customer\Process\CustomerEditSubDomainEvent;
use App\Events\Customer\Validation\ValidationCustomerEditSubDomainEvent;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCustomerController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function customerNew(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            //UPDATE OR CREATE CUSTOMER
            $validation = event(new ValidationCustomerNewEvent($arr_parametros), $request);

            if (!$validation[0]["success"]) {
                return $this->crearRespuesta($validation);
            }

            $customerNew = event(new CustomerNewEvent($arr_parametros), $request);

            $success = $customerNew[0]["success"];
            $title_response = $customerNew[0]["status"];
            $text_response = "Datos registrados"; //$customerNew[0]["message"];
            $data = json_decode($customerNew[0]["data"], true);

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion";
            $last_action = "NA";
            $error = (object)$this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function editSubDomain(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();

            //UPDATE OR CREATE CUSTOMER
            $validation = event(new ValidationCustomerEditSubDomainEvent($arr_parametros), $request);

            if (!$validation[0]["success"]) {
                return $this->crearRespuesta($validation);
            }

            $customer = event(new CustomerEditSubDomainEvent($arr_parametros), $request);

            $success = $customer[0]["success"];
            $title_response = $customer[0]["status"];
            $text_response = $customer[0]["message"];
            $data = $customer[0]["data"];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion";
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

}
