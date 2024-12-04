<?php

namespace App\Listeners\Subscription\Process;

use App\Common\SubscriptionStateCodes;
use App\Common\TransactionStateCodes;
use App\Events\Subscription\Process\ProcessSubscriptionConfirmEvent;
use App\Helpers\Pago\HelperPago;
use App\Models\BblClientesPasarelas;
use App\Models\BblPlan;
use App\Models\BblSuscripcion;
use App\Models\BblSuscripcionCargos;
use App\Models\BblClientes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionConfirmListener extends HelperPago
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
     * @param ProcessSubscriptionConfirmEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessSubscriptionConfirmEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            $stateCode = $fieldValidation["x_cod_transaction_state"];
            $stateCodeText = $fieldValidation["x_transaction_state"];
            $transactionClientId = $fieldValidation["x_cust_id_cliente"];
            $epaycoReference = $fieldValidation["x_ref_payco"];
            $totalAmount = $fieldValidation["x_amount_ok"];
            $amount = $fieldValidation["x_amount"];
            $invoiceId = $fieldValidation["x_id_invoice"];
            $transactionId = $fieldValidation['x_transaction_id'];
            $currencyCode = $fieldValidation['x_currency_code'];
            $signature = $fieldValidation['x_signature'];
            $suscriptionId = $fieldValidation['x_extra1'];
            $description = $fieldValidation['x_description'];
            $suscripcionClienteId = $fieldValidation['x_extra2'];
            $transaccionId = $fieldValidation['x_extra3'];
            $iva = $fieldValidation['x_tax'];
            $cardNumber = $fieldValidation['x_cardnumber'];
            $franchise = $fieldValidation['x_franchise'];

            $clientDataPasarela = BblClientesPasarelas::where('cliente_id', $transactionClientId)->where('estado', true)->first();
            $calculateSignature = hash('sha256',
                $transactionClientId . '^' . $clientDataPasarela->key_cli . '^' . $epaycoReference .
                '^' . $transactionId . '^' . $totalAmount . '^' . $currencyCode
            );

            $data = array($suscriptionId, $epaycoReference, $invoiceId, $totalAmount,
                $amount, $currencyCode, $stateCodeText, $suscripcionClienteId, $transaccionId, $description, $iva, $cardNumber, $franchise);

            if ($stateCode == TransactionStateCodes::ACCEPTED && ($signature == $calculateSignature)) {
                $this->enablePlanBBL($data, $suscriptionId, $totalAmount);
            } elseif ($signature == $calculateSignature &&
                ($stateCode == TransactionStateCodes::FAILED ||
                    $stateCode == TransactionStateCodes::REJECTED ||
                    $stateCode == TransactionStateCodes::REVERSED ||
                    $stateCode == TransactionStateCodes::EXPIRED ||
                    $stateCode == TransactionStateCodes::ABANDONED ||
                    $stateCode == TransactionStateCodes::CANCELLED ||
                    $stateCode == TransactionStateCodes::ANTI_FRAUD ||
                    $stateCode == TransactionStateCodes::HELD

                )) {
                $this->notifyClient($suscriptionId, $data, $stateCode);

            }

            $arr_respuesta['success'] = true;
            $arr_respuesta['titleResponse'] = "Confirm successful";
            $arr_respuesta['textResponse'] = "Confirm successful";
            $arr_respuesta['lastAction'] = "Subscription Confirm";
            $arr_respuesta['data'] = [];

            return $arr_respuesta;

        } catch (\Exception $exception) {

            Log::info($exception);
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

    private function notifyClient($suscriptionId, $data, $stateCode)
    {
        $days_to_add = 2;

        $subscription = BblSuscripcion::where('suscripcion_sdk_id', $suscriptionId)->orderBy('created_at', 'DESC')->first();

        if (!empty($subscription) && $subscription->estado == SubscriptionStateCodes::ACTIVE) {

            if ($subscription->estado != SubscriptionStateCodes::INTEGRATION && $subscription->estado != SubscriptionStateCodes::CANCELED && $stateCode != TransactionStateCodes::REVERSED) {
                //DATES

                $current_date = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'))->setTime(0, 0, 0);
                $expire_date = Carbon::createFromFormat('Y-m-d', Carbon::parse($subscription->fecha_renovacion)->format('Y-m-d'))->setTime(0, 0, 0);
                $new_days = Carbon::parse($expire_date)->addDays($days_to_add)->format('Y-m-d');

                //DATE RANGES
                $startDate = $expire_date;
                $endDate = $new_days;

                $dateRange = CarbonPeriod::create($startDate, $endDate)->toArray();
                #array_push($dateRange, Carbon::createFromFormat('Y-m-d', $endDate)->setTime(0, 0, 0));

                $user = $subscription->cliente;
                $subscription_link = config('app.BASE_URL_SUBSCRIPTION');

                //CHECK IF EXPIRE DATE IS TODAY
                if (($current_date->eq($expire_date)) || ($current_date->gt($expire_date))) {

                    //COMPARE DATES
                    foreach ($dateRange as $d) {

                        if ($current_date->eq($d)) {
                            
                            //SEND EMAIL
                            try {
                                /*$this->EmailNotificationsBBL($user->email, "email/send-failed-debit-attempt",
                                [
                                    "clientName" => $user->nombre . " " . $user->apellido,

                                ]);*/
                                $this->emailPanelRest("La renovación de Tu Tienda se encuentra pendiente", $user->email, "babilonia_intento_debito_fallido",
                                    [
                                        "clientName" => $user->nombre . " " . $user->apellido,
    
                                    ]);
                            } catch (\Exception $exception) {
                                Log::info($exception);

                            }
                      

                            break;
                        }

                    }

                    $new_days = Carbon::createFromFormat('Y-m-d', $new_days)->setTime(0, 0, 0);

                    //CHECK IF EXPIRE DATE IS TODAY
                    if ($current_date->greaterThanOrEqualTo($new_days)) {

                        $this->disabledPlanBBL($data, $subscription->id);
                        //SEND DISABLE SUBSCRIPTION EMAIL
                        /*$this->EmailNotificationsBBL($user->email, "email/send-service-suspension",
                            [
                                "clientName" => $user->nombre . " " . $user->apellido,
                                "urlSuscription" => $subscription_link,
                            ]);*/
                        $this->emailPanelRest("Actualiza tu cuenta: Tu Tienda se encuentra suspendida", $user->email, "babilonia_suspencion_servicio",
                            [
                                "clientName" => $user->nombre . " " . $user->apellido,
                                "urlSuscription" => $subscription_link,
                            ]);
                            
                        //UPDATE CANCELATION DATE

                        $subscription->fecha_cancelacion = $current_date;
                        $subscription->save();

                    }

                }
            } elseif ($stateCode == TransactionStateCodes::REVERSED) {
                $this->disabledPlanBBL($data, $subscription->id, 'Reversada');
            } 
            elseif ($subscription->estado === SubscriptionStateCodes::INTEGRATION &&
                $stateCode !== TransactionStateCodes::ACCEPTED &&
                $stateCode !== TransactionStateCodes::PENDING) {
                $this->disabledPlanBBL($data, $subscription->id, 'Rechazada');
            }

        }else{
            $this->disabledPlanBBL($data, $subscription->id, 'Rechazada');

        }

    }

    private function enablePlanBBL($data, $suscriptionId, $totalAmount)
    {
        $bblsuscription = BblSuscripcion::where('suscripcion_sdk_id', $suscriptionId)->orderBy('created_at', 'DESC')->first();

        if (is_null($bblsuscription)) {
            throw new \Exception("Invalid suscriptionId");
        }
        
        //GETTING LAST ACTIVE SUBSCRIPTION FROM THE USER
        $lastActiveSubscriptions = BblSuscripcion::where('bbl_cliente_id', $bblsuscription->bbl_cliente_id)->where('estado', SubscriptionStateCodes::ACTIVE)->first();


        if (!empty($lastActiveSubscriptions)) {
                try {
                    $this->cancelSubscription($lastActiveSubscriptions->suscripcion_sdk_id);
                    $lastActiveSubscriptions->estado = SubscriptionStateCodes::CANCELED;
                    $lastActiveSubscriptions->fecha_cancelacion = Carbon::now()->format('Y-m-d');
                    $lastActiveSubscriptions->save();
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                    throw new \Exception($exception);

                }
            }


        $bbl_plan = BblPlan::find($bblsuscription->bbl_plan_id);
        $now = Carbon::now()->format('Y-m-d');
        $nextProductStatus = SubscriptionStateCodes::ACTIVE;

        $renovationDate = Carbon::parse($bblsuscription->fecha_renovacion)->format('Y-m-d');
        $bblsuscription->fecha_renovacion = Carbon::parse($renovationDate)->addMonths($bbl_plan->periodicidad);
        $bblsuscription->fecha_cancelacion = Carbon::parse($renovationDate)->addMonths($bbl_plan->periodicidad);

        $bblsuscription->estado = $nextProductStatus;
        $bblsuscription->save();

        $user = $bblsuscription->cliente;
        $subscription_link = config('app.BASE_URL_SUBSCRIPTION');

        $bbl_client = BblClientes::find($bblsuscription->bbl_cliente_id);
        $bbl_client->prueba_gratis =1;
        $bbl_client->save();

        //SEND SUCCESSFUL DEBIT
        /*$this->EmailNotificationsBBL($user->email, "email/send-successful-suscription-debit-email",
            [
                "clientName" => $user->nombre . " " . $user->apellido,
                "urlSuscription" => $subscription_link,
                "price" => (float) $totalAmount,
            ]);*/
        $this->emailPanelRest("Débito exitoso", $user->email, "babilonia_debito_suscripcion_exitosa",
            [
                "clientName" => $user->nombre . " " . $user->apellido,
                "urlSuscription" => $subscription_link,
                "price" => (float) $totalAmount,
            ]);

        $this->createBBLSucriptionCharge($data, $now, 'Aprobada');

    }

    private function disabledPlanBBL($data, $suscriptionId, $status = 'Fallida')
    {
        $now = Carbon::now()->format('Y-m-d');
        $bblsuscription = BblSuscripcion::find($suscriptionId);
        if (is_null($bblsuscription)) {
            throw new \Exception("Invalid suscriptionId");
        }

        $bblsuscription->fecha_cancelacion = Carbon::now()->format('Y-m-d');
        $bblsuscription->estado = SubscriptionStateCodes::CANCELED;
        $bblsuscription->save();

        $this->cancelSubscription($bblsuscription->suscripcion_sdk_id);
        $this->createBBLSucriptionCharge($data, $now, $status);

    }

    private function createBBLSucriptionCharge($data, $now, $state)
    {
        list($suscriptionId, $epaycoReference, $invoiceId, $totalAmount,
            $amount, $currencyCode, $stateCodeText, $suscripcionClienteId, $transaccionId, $description, $iva, $cardNumber, $franchise) = $data;
        $bblSuscripcionCargosNew = new BblSuscripcionCargos();

        $bblSuscripcionCargosNew->ref_payco = $epaycoReference;
        $bblSuscripcionCargosNew->factura = $invoiceId;
        $bblSuscripcionCargosNew->descripcion = $description;
        $bblSuscripcionCargosNew->valor = $totalAmount;
        $bblSuscripcionCargosNew->valor_neto = $amount;
        $bblSuscripcionCargosNew->moneda = $currencyCode;
        $bblSuscripcionCargosNew->recibo = $epaycoReference;
        $bblSuscripcionCargosNew->fecha = $now;
        $bblSuscripcionCargosNew->fecha_confirmacion = $now;
        $bblSuscripcionCargosNew->respuesta = $state;
        $bblSuscripcionCargosNew->estado = $stateCodeText;
        $bblSuscripcionCargosNew->fecha_confirmacion = $now;
        $bblSuscripcionCargosNew->suscription_id = $suscriptionId;
        $bblSuscripcionCargosNew->transaccion_id = $transaccionId;
        $bblSuscripcionCargosNew->suscripcion_cliente_id = $suscripcionClienteId;
        $bblSuscripcionCargosNew->confirmacion = true;
        $bblSuscripcionCargosNew->iva = $iva;
        $bblSuscripcionCargosNew->tarjeta_nro = $cardNumber;
        $bblSuscripcionCargosNew->tarjeta_franquicia = $franchise;

        $bblSuscripcionCargosNew->save();
    }
}
