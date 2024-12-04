<?php

namespace App\Listeners\Services;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Models\ServiceSms;
use App\Libs\Sms\Facade\Sms;
use Illuminate\Http\Request;
use App\Models\Transacciones;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Messages\CommonText;
use App\Models\DetalleTransacciones;
use App\Events\Services\ServicesSmsEvent;

class ServicesSmsListener extends HelperPago
{
    public $model;
    public $request;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->model = new ServiceSms();
    }

    /**
     * Handle function
     *
     * @param ServicesSmsEvent $event
     * @return JsonResponse
     */
    public function handle(ServicesSmsEvent $event)
    {
        try {
            // Envio de SMS
            $response = Sms::to($event->params['recipient'])
                ->message($event->params['message'])
                ->send();

            $transaction = null;
            $transactionDetail = null;

            if (isset($event->params['extras']) && isset($event->params['extras']["transaction_id"])) {
                $transactionId = $event->params['extras']["transaction_id"];
                $transaction = Transacciones::where('Id', $transactionId)->first();
                $transactionDetail = DetalleTransacciones::where('pago', $transactionId)->first();
            }

            $timeArray = explode(" ", microtime());
            $timeArray[0] = str_replace('.', '', $timeArray[0]);

            $sms_log = [
                "id" => Uuid::uuid4()->toString(),
                "fecha" => date("c"),
                "fecha_actualizacion" => date("c"),
                CommonText::CLIENT_ID => $event->params['clientId'],
                "provider" => "hablameco",
                "recipient" => $event->params['recipient'],
                "message" => $event->params['message'],
                "message" => $event->params['message'],
                "request" => $event->params,
                "response" => $response->getContent(),
                "transaction" => $transaction ? [
                    "fecha"=>$transaction->fecha,
                    "fechaPago"=>$transaction->fechapago,
                    "refPayco"=>$transaction->Id,
                    "valor" => $transaction->valortotal,
                    "franquicia" => $transaction->franquicia,
                    "banco" => $transaction->nombre_banco,
                    "estado" => $transaction->estado,
                    "tipoDoc" => $transactionDetail ? $transactionDetail->tipo_doc : null,
                    "documento" => $transactionDetail ? $transactionDetail->cedula : null,
                    "extras" => json_decode($transaction->extras),
                ] : null,
            ];

            $this->elasticBulkUpload(["indice" => "sms_log", "data" => [$sms_log]]);

            return $response->getContent();
        } catch (\Exception $ex) {
            return json_encode([
                'success' => false,
                'titleResponse' => 'Error envio de SMS',
                'textResponse' => 'Error registrando el SMS',
                'lastAction' => 'save_send_message',
                'data' => ['message' => $ex->getMessage()]
            ]);
        }
    }
}
