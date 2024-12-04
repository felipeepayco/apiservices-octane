<?php

namespace App\Listeners\Payments\Process;

use App\Events\Payments\Process\ProcessChangePlanEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Controllers\ApiSubscriptionController;
use App\Http\Lib\Utils;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use App\Models\BblClientesCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessChangePlanListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ProcessChangePlanEvent $event)
    {
        $test = false;
        $util = new Utils();
        try {
            $fieldValidation = $event->arr_parametros;
            $clienteId = $fieldValidation["clientId"];
            $idPlan = $fieldValidation["id_plan"];

            //nueva suscripcion
            $BblSuscripcion = BblClientes::select("cliente_sdk_id")->find($clienteId);
            $customerId = $BblSuscripcion->cliente_sdk_id;
            $dataCustomer = $this->getCustomerBbl($clienteId, $customerId);
            foreach ($dataCustomer->data->cards as $card) {
                if ($card->default) {
                    $tokenCard = $card->token;
                }
            }

            $datosTokenizacion = BblClientesCard::where('token', $tokenCard)->first();

            $docType = $datosTokenizacion["doc_type"];
            $docNumber = $datosTokenizacion["doc_number"];
            $urlConfirmacion = config('app.URL_KHEPRI') . "/subscriptions/confirm";
            $methodConfirmacion = "POST";
            $this->request->replace(['id_plan' => $idPlan, 'doc_type' => $docType, 'doc_number' => $docNumber, 'clientId' => $clienteId, 'url_confirmation' => $urlConfirmacion, "method_confirmation" => $methodConfirmacion]);

            $apiSubscriptionController = new ApiSubscriptionController($this->request);

            $resSuscripcion = $apiSubscriptionController->subscriptionNew($this->request);

            $dataSuscripcion = $resSuscripcion->getData();
            if (isset($dataSuscripcion->success)) {
                if (!$dataSuscripcion->success) {
                    return (array) $dataSuscripcion;
                }

            } else {
                return $dataSuscripcion;
            }
            

            $resCharge = $apiSubscriptionController->charge($this->request);
            $dataCharge = $resCharge->getData();
            if (isset($dataCharge->success)) {
                if (!$dataCharge->success) {
                    return (array) $dataCharge;
                }
            } else {

                return $dataCharge;
            }

            $success = isset($dataCharge->data->data->estado) ? true : false;

            if ($success) {
                $titleResponse = 'response from plan';
                $textResponse = "response from plan";
                $lastAction = 'changePlan';

            } else {
                $titleResponse = 'error';
                $textResponse = "There was an internal error please try later";
                $lastAction = 'changePlan';
            }

            $data = [];
            if ($success) {
                $data = ["chargeStatus" => $this->setSubscriptionState($dataCharge->data->data->estado), "subscriptionId" => $dataCharge->data->data->extras->extra1];

            }else{
                $data = ["chargeStatus" => null, "subscriptionId" => $dataSuscripcion->data->id];

            }
        } catch (Exception $exception) {

            Log::info($exception);
            $success = false;
            $titleResponse = 'Error';
            $textResponse = "Error inesperado al consultar las transacciones con los parametros datos";
            $lastAction = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $titleResponse;
        $arrResponse['textResponse'] = $textResponse;
        $arrResponse['lastAction'] = $lastAction;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

    public function setSubscriptionState($state)
    {
        $output = 0;
        switch ($state) {
            case 'Aceptada':
                $output = 1;
                break;

            case 'Pendiente':
                $output = 5;
                break;

            case 'Rechazada':
                $output = 3;
                break;

            case 'Reversada':
                $output = 7;
                break;

            case 'Fallida':
                $output = 3;
                break;

        }

        return $output;
    }
}
