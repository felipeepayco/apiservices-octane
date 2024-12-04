<?php

namespace App\Listeners\Customer\Process;

use App\Events\Customer\Process\CustomerNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use App\Models\BblClientes;
use Illuminate\Http\Request;

class CustomerNewListener extends HelperPago
{
    private $arr_respuesta = [];

    /**
     * CustomerNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param CustomerNewEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(CustomerNewEvent $event)
    {

        $params = $event->arr_parametros;
        try {
            $client = BblClientes::find($params["clientId"]);
            $response2 = (object) ['status' => true];
            if ($client->cliente_sdk_id && $client->cliente_sdk_id !== '') {
                //update CUSTOMER
                unset($params['address']);
                $params['name'] = $params['name'] . " " . $params['last_name'];
                $response = $this->customerUpdate($client->cliente_sdk_id, (array)$params);
                if ($response->status) {
                    $response2 = $this->addNewTokenBbl([
                        "token_card" => $params['token_card'],
                        "customer_id" => $client->cliente_sdk_id
                    ]);
                }
            } else {
                //CREATE CUSTOMER
                $response = $this->createCustomer($params);
                $client->cliente_sdk_id = $response->data->customerId;
                $client->save();
            }
            $data = json_encode(['data' => $response->data, 'dataToken' => $response2]);
            $this->arr_respuesta['success'] = $response->status && $response2->status;
            $this->arr_respuesta['status'] = $response->status && $response2->status;
            $this->arr_respuesta['message'] = $response->message ?? '';
            $this->arr_respuesta['data'] = $data;
    
            return $this->arr_respuesta;
        } catch (\Exception$exception) {
            $success = false;
            $this->arr_respuesta['status'] = false;
            $this->arr_respuesta['success'] = false;
            $this->arr_respuesta['message'] = "Error registro customer";
            $error = (object)$this->getErrorCheckout('E0100');
            $validate = new Validate();
            $errordebug = [
                "msj" => $exception->getMessage(),
                "line" => $exception->getLine(),
                "file" => $exception->getFile(),
                "code" => $exception->getCode(),
                "data" => [$client->cliente_sdk_id, (array)$params]
            ];
            $validate->setError($error->error_code, $error->error_message);
            $this->arr_respuesta['data'] = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage, 'debug' => $errordebug);

        }

    }

}
