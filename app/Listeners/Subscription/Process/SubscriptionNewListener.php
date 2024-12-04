<?php

namespace App\Listeners\Subscription\Process;

use App\Events\Subscription\Process\SubscriptionNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Models\BblClientes;
use App\Models\BblPlan;
use App\Models\BblSuscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Listeners\Services\EmailService;

class SubscriptionNewListener extends HelperPago
{
    private $arr_respuesta = [];

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function handle(SubscriptionNewEvent $event)
    {

        $params = $event->arr_parametros;
        $response = null;

        //CHECK IF PLAN IS NOT FREE
        if (!empty($params["id_plan"])) {
        
            $response = $this->createSubscription($params);

        }

        if (!empty($response)) {
            if ($response->status) {

                $plan = BblPlan::where('plan_suscripcion_id', $params["id_plan"])->first();
                $bbl_suscripcion = BblSuscripcion::create(
                    [
                        "bbl_plan_id" => $plan->id,
                        "bbl_cliente_id" => $params["clientId"],
                        "fecha_inicio" => Carbon::parse($response->current_period_start)->format('Y-m-d'),
                        "fecha_renovacion" => Carbon::parse($response->current_period_end)->format('Y-m-d'),
                        "fecha_cancelacion" => Carbon::parse($response->current_period_end)->format('Y-m-d'),
                        "estado" => 7,
                        "fecha_creacion" => Carbon::now(),
                        "suscripcion_sdk_id" => $response->id,
                        "created_at" => Carbon::now(),
                    ]);

            
                $this->arr_respuesta['success'] = $response->success;
                $this->arr_respuesta['status'] = $response->status;
                $this->arr_respuesta['message'] = $response->type;
                $this->arr_respuesta['data'] = $bbl_suscripcion;
            } else {
                $this->arr_respuesta['success'] = false;
                $this->arr_respuesta['status'] = $response->status;
                $this->arr_respuesta['message'] = $response->message;
                $this->arr_respuesta['data'] = json_encode($response);
            }
        } else {
            //CREATE A FREE PLAN SUBSCRIPTION
            $plan = BblPlan::where('plan_suscripcion_id', 'Prueba gratis')->first();
            $bbl_subscripcion_response = BblSuscripcion::create(
                [
                    "bbl_plan_id" => $plan->id,
                    "bbl_cliente_id" => $params["clientId"],
                    "fecha_inicio" => Carbon::now(),
                    "fecha_renovacion" => Carbon::parse(Carbon::now())->addDays(30),
                    "fecha_cancelacion" => Carbon::parse(Carbon::now())->addDays(30),
                    "estado" => 1,
                    "fecha_creacion" => Carbon::now(),
                    "created_at" => Carbon::now(),
                ]);

            $this->arr_respuesta['success'] = true;
            $this->arr_respuesta['status'] = 200;
            $this->arr_respuesta['message'] = "Suscripcion almacenada con exito";
            $this->arr_respuesta['data'] = $bbl_subscripcion_response;

            try {
                // $this->enviarEmailSuscripcionGratis($params["clientId"]);
                /*$bblCliente = BblClientes::find($params["clientId"]);
                $toEmail = $bblCliente->email;
                $clientName = $bblCliente->nombre . " " . $bblCliente->apellido;
                $this->EmailNotificationsBBL($toEmail, "email/send-free-trial-activation-email", ["clientName" => $clientName]);*/
            } catch (Exception $e) {

                Log::info($e);
                $this->arr_respuesta['success'] = false;
                $this->arr_respuesta['status'] = 500;
                $this->arr_respuesta['message'] = "Error al enviar correo del plan gratis";
                $this->arr_respuesta['data'] = "";
            }
        }
        return $this->arr_respuesta;

    }
    
    private function enviarEmailSuscripcionGratis($clienteId){
 
        $bblCliente=BblClientes::find($clienteId);
        $subject="Estás listo para digitalizar tus ventas: Inicia tu prueba gratuita";
        $toEmail=$bblCliente->email;
        $clientName= $bblCliente->nombre." ".$bblCliente->apellido;
        $emailService = new EmailService();
        $emailService->enviarEmailSuscripcionGratisDeprecated($subject, $toEmail, $clientName);
        
    }


}
