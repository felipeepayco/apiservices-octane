<?php

namespace App\Http\Controllers;

use App\Events\Payments\Process\ProcessChangePlanEvent;
use App\Events\Payments\Process\ProcessChargeEvent;
use App\Events\Payments\Validation\ValidationChangePlanEvent;
use App\Events\Payments\Validation\ValidationChargeEvent;
use App\Events\Subscription\Process\ProcessSubscriptionConfirmEvent;
use App\Events\Subscription\Process\ProcessSubscriptionEvent;
use App\Events\Subscription\Process\SubscriptionCancelEvent;
use App\Events\Subscription\Process\SubscriptionNewEvent;
use App\Events\Subscription\Validation\ValidationSubscriptionConfirmEvent;
use App\Events\Subscription\Validation\ValidationSubscriptionEvent;
use App\Events\Subscription\Validation\ValidationSubscriptionNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Models\BblClientes;
use App\Models\BblSuscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Common\SubscriptionStateCodes;

class ApiSubscriptionController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function subscriptionNew(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            //UPDATE OR CREATE SUBSCRIPTION
            $validation = event(new ValidationSubscriptionNewEvent($arr_parametros), $request);

            if (!$validation[0]["success"]) {
                return $this->crearRespuesta($validation);
            }

            $new = event(new SubscriptionNewEvent($arr_parametros), $request);

            $success = $new[0]["success"];
            $title_response = $new[0]["status"];
            $text_response = $new[0]["message"];
            $data = is_string($new[0]["data"]) ? json_decode($new[0]["data"], true) : $new[0]["data"];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function subscriptionCancel(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $cancel = event(new SubscriptionCancelEvent($arr_parametros), $request);

            $data = [];
            $success = $cancel[0]["success"];

            if ($success) {
                $title_response = $cancel[0]["status"];
                $text_response = $cancel[0]["message"];
                if ($cancel[0]["data"]) {
                    $data = json_decode($cancel[0]["data"], true);

                }
            } else {
                $title_response = $cancel[0]["titleResponse"];
                $text_response = $cancel[0]["textResponse"];
                $data = $cancel[0]["data"];

            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function subscriptionRenew(Request $request)
    {

        try {

            $arrParametros = $request->request->all();
            $data = [];
            $success = true;
            $titleResponse = "Success";
            $textResponse = "Successfully renewed subscription";

            $client = BblClientes::find($arrParametros["clientId"]);

            //CREATE SUBSCRIPTION

            $request->merge([
                "doc_type" => $client->tipo_documento->codigo,
                "doc_number" => $client->documento,
                "url_confirmation" => config('app.URL_KHEPRI') . "/subscriptions/confirm",
                "method_confirmation" => "POST",
            ]);

            $subscription = BblSuscripcion::where('bbl_cliente_id', $arrParametros["clientId"])->where('estado', SubscriptionStateCodes::ACTIVE)->orderBy('created_at', 'DESC')->first();

            if (!empty($subscription) && $subscription->suscripcion_sdk_id) {
                $checkSubscription = $this->getSubscriptionsBbl($subscription->suscripcion_sdk_id);
                if ($checkSubscription->status_plan == "active" || $checkSubscription->status_plan == "retry") {

                    $this->cancelSubscription($subscription->suscripcion_sdk_id);

                }

            }

            $subscription = json_decode($this->subscriptionNew($request)->getContent(), true);

            //MAKE CHARGE IF PLAN IS NOT FREE

            if ($request->id_plan != "") {
                $charge = json_decode($this->charge($request)->getContent(), true);
                $success = $charge["success"];
                $titleResponse = $charge["textResponse"];
                $textResponse = $charge["textResponse"];

                if (isset($charge["data"])) {

                    if (isset($charge["data"]["data"]["chargeStatus"]) || isset($charge["data"]["error"]["chargeStatus"])) {
                        $chargeStatus = ($charge["success"]) ? $charge["data"]["data"]["chargeStatus"] : $charge["data"]["error"]["chargeStatus"];
                        $suscripcionSdkId = ($charge["success"]) ? $charge["data"]["data"]["extras"]["extra1"] : $charge["data"]["error"]["extras"]["extra1"];
                        $subscription = BblSuscripcion::where('suscripcion_sdk_id', $suscripcionSdkId)->orderBy('created_at', 'DESC')->first();
                        $subscription->load('plan');

                        $subscription = $subscription->toArray();
                        $subscription["estado"] = $chargeStatus;


                    }

                }

            } else {
                $subscription = BblSuscripcion::find($subscription["data"]["id"]);
                $client->prueba_gratis = 1;
                $client->save();
                $subscription->load('plan');

            }

        } catch (\Exception $exception) {

            Log::info($exception);
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $lastAction = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'data' => $subscription,
        );

        return $this->crearRespuesta($response);
    }

    public function changePlan(Request $request)
    {
        try {

            $arrParametros = $request->request->all();
            $validationGeneral = event(
                new ValidationChangePlanEvent($arrParametros),
                $request);
            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $resData = event(
                new ProcessChangePlanEvent($validationGeneral[0]),
                $request
            );



            $success = $resData[0]['success'];
            $titleResponse = $resData[0]['titleResponse'];
            $textResponse = $resData[0]['textResponse'];
            $lastAction = $resData[0]['lastAction'];
            $data = $resData[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error query database" . $exception->getMessage();
            $lastAction = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
        }

        $suscription = [];

        $suscripcionSdkId = ($success) ? $data["subscriptionId"] : $data->error->extras->extra1;
        $suscription = BblSuscripcion::where('suscripcion_sdk_id', $suscripcionSdkId)->orderBy('created_at', 'DESC')->first();
        $suscription->load('plan');

        if ($success) {

            $suscription = $suscription->toArray();
            $suscription["estado"] = $data["chargeStatus"];

        }



        $response = array(
            'success' => $success,
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => $lastAction,
            'data' => $suscription,
        );

        return $this->crearRespuesta($response);
    }
    public function charge(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneral = event(
                new ValidationChargeEvent($arr_parametros),
                $request);
            if (!$validationGeneral[0]["success"]) {
                return $this->crearRespuesta($validationGeneral[0]);
            }

            $resData = event(
                new ProcessChargeEvent($validationGeneral[0]),
                $request
            );
            $success = $resData[0]['success'];
            $title_response = $resData[0]['titleResponse'];
            $text_response = $resData[0]['textResponse'];
            $last_action = $resData[0]['lastAction'];
            $data = $resData[0]['data'];
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
                'errors' => $validate->errorMessage,
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

    public function confirmChangeSubscription(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneralSubcriptionCreate = event(
                new ValidationSubscriptionConfirmEvent($arr_parametros),
                $request
            );
            if (!$validationGeneralSubcriptionCreate[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSubcriptionCreate[0]);
            }

            $subcription = event(
                new ProcessSubscriptionConfirmEvent($validationGeneralSubcriptionCreate[0]),
                $request
            );

            $success = $subcription[0]['success'];
            $title_response = $subcription[0]['titleResponse'];
            $text_response = $subcription[0]['textResponse'];
            $last_action = $subcription[0]['lastAction'];
            $data = $subcription[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function subscription(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
            $validationGeneralSubcriptionCreate = event(
                new ValidationSubscriptionEvent($arr_parametros),
                $request
            );
            if (!$validationGeneralSubcriptionCreate[0]["success"]) {
                return $this->crearRespuesta($validationGeneralSubcriptionCreate[0]);
            }

            $subcription = event(
                new ProcessSubscriptionEvent($validationGeneralSubcriptionCreate[0]),
                $request
            );

            $success = $subcription[0]['success'];
            $title_response = $subcription[0]['titleResponse'];
            $text_response = $subcription[0]['textResponse'];
            $last_action = $subcription[0]['lastAction'];
            $data = $subcription[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }
}
