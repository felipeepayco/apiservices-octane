<?php

namespace App\Listeners\Subscription\Process;

use App\Common\SubscriptionStateCodes;
use App\Common\TransactionStateCodes;
use App\Events\Subscription\Process\ProcessSubscriptionEvent;
use App\Helpers\Pago\HelperPago;
use App\Models\BblSuscripcion;
use App\Models\BblSuscripcionCargos;
use App\Models\Clientes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Models\BBLClientes;
class ProcessSubscriptionListener extends HelperPago
{
    /**
     * SubscriptionCreateListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @param ProcessSubscriptionEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessSubscriptionEvent $event)
    {
           try {
                $fieldValidation = $event->arr_parametros;
                $clientId = $fieldValidation["clientId"];
                $planArray = BblSuscripcion::checkPlanByDate($clientId,12, [1, 2, 5, 10], false, true);
                // Obtener el primer resultado (si existe) o asignar null a $plan
                $plan = !empty($planArray) ? BblSuscripcion::formatResponsePlan($planArray[0]) : false;
                // Obtener el modelo de cliente por su ID
                $clientBBL = BBLClientes::find($clientId);

                if (!$this->initSdkBbl()) {
                    $arr_respuesta['success'] = false;
                    $arr_respuesta['titleResponse'] = "initSdk error";
                    $arr_respuesta['textResponse'] = "initSdk error";
                    $arr_respuesta['lastAction'] = "Subscription list";
                    return $arr_respuesta;
                }

                $client =$this->getCustomerBblV2($clientBBL->cliente_sdk_id);
                if(isset($client) && $client!=false){
                    $customer=$client->status ? $client->data : null;
                }else{
                    $customer=null;
                }
                $data= ["plan" => $plan, "customer" => $customer];
            
                $arr_respuesta['success'] = true;
                $arr_respuesta['titleResponse'] = "Datos de la suscripción vende";
                $arr_respuesta['textResponse'] = "Datos de la suscripción vende";
                $arr_respuesta['lastAction'] = "Consulta suscripcion";
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;

        } catch (\Exception$exception) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = "Error " . $exception->getMessage();
            $arr_respuesta['textResponse'] = "Error " . $exception->getMessage();
            $arr_respuesta['lastAction'] = "Subscription Confirm";
            $arr_respuesta['data'] = [
                "totalErrors" => 1,
                "errors" => [
                    [
                        "codError" => 500,
                        "errorMessage" => "Error " . $exception->getMessage(),
                    ],
                ],
            ];

            return $arr_respuesta;
        }
    }

}
