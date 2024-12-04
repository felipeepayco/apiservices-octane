<?php

namespace App\Listeners\Subscription\Process;

use App\Common\PlanTypesCodes;
use App\Common\SubscriptionStateCodes;
use App\Events\Subscription\Process\SubscriptionCancelEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BblSuscripcion;
use Illuminate\Http\Request;

class SubscriptionCancelListener extends HelperPago
{
    private $arrRespuesta = [];
    private $validate;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
        $this->validate = new Validate();

    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function handle(SubscriptionCancelEvent $event)
    {

        $params = $event->arr_parametros;
        $response = null;

        //GETTING CURRENT SUSCRIPTION
        $subscription = BblSuscripcion::where('bbl_cliente_id', $params["clientId"])->where('estado', SubscriptionStateCodes::ACTIVE)->orderBy('created_at', 'DESC')->first();

        if ($subscription->estado != SubscriptionStateCodes::CANCELED) {
            //CHECK IF PLAN IS NOT FREE
            $response = $this->checkPlan($subscription);

            if (!empty($response)) {

                if ($response["success"]) {

                    //CANCEL PLAN
                    $subscription->estado = SubscriptionStateCodes::CANCELED;
                    $subscription->save();

                    $this->arrRespuesta['success'] = $response["success"];
                    $this->arrRespuesta['status'] = $response["status"];
                    $this->arrRespuesta['message'] = $response["type"];
                    $this->arrRespuesta['data'] = json_encode($response);
                } else {
                    $this->arrRespuesta['success'] = false;
                    $this->arrRespuesta['status'] = $response["status"];
                    $this->arrRespuesta['message'] = $response["message"];
                    $this->arrRespuesta['data'] = json_encode($response);
                }
            }
        } else {
            $this->arrRespuesta['success'] = true;
            $this->arrRespuesta['status'] = 200;
            $this->arrRespuesta['message'] = "This plan is already canceled";
            $this->arrRespuesta['data'] = [];
        }

        if ($this->validate->totalerrors > 0) {

            $success = false;
            $lastAction = 'validation data save';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';

            $data = [
                'totalErrors' => $this->validate->totalerrors,
                'errors' => $this->validate->errorMessage,
            ];
            $response = [
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => $data,
            ];

            $this->saveLog(2, $params["clientId"], '', $response, 'subscription_cancel');

            return $response;
        }

        return $this->arrRespuesta;

    }

    private function checkPlan(BblSuscripcion $subscription)
    {
        $arrRespuesta = [];
        $response = null;

        $arrRespuesta['success'] = true;
        $arrRespuesta['status'] = 200;
        $arrRespuesta['type'] = "Plan successfully canceled";
        $arrRespuesta['data'] = [];
        $response = $arrRespuesta;

        if ($subscription->suscripcion_sdk_id) {
            if ($subscription->plan->nombre != PlanTypesCodes::FREE_PLAN) {
                //CANCEL SUBSCRIPTION
                $response = $this->cancelSubscription($subscription->suscripcion_sdk_id);
                $response = collect($response)->toArray();

            }
        }

        return $response;

    }

}
