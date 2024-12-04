<?php

namespace App\Listeners\PaycoLink\Process;

use App\Events\PaycoLink\Process\ProcessPaycoLinkPaymentCreateEvent;
use App\Http\Validation\Validate as Validate;
use App\Models\CobroLogTransaccion;
use App\Helpers\Pago\HelperPago;
use App\Models\Transacciones;
use App\Models\Cobros;
use Exception;
use DateTime;

class ProcessPaycoLinkPaymentCreateListener extends HelperPago
{

    public $success = true;
    public $titleResponse = 'PaycoLink Payment';
    public $textResponse = '';
    public $lastAction = 'process_payco_link_payment';
    public $data = [];


    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ProcessPaycoLinkPaymentCreateEvent $event)
    {
        try {
            $data = $event->arr_parametros;
            $existsLog = $this->existsCobroLogTransaction($data);
            $paycoLink = Cobros::findOrFail($data['paycoLinkId']);
            $transaction = Transacciones::findOrFail($data['transaction']);;
            
            if ($this->discountPayment($transaction) && !$existsLog) {
                $transaction->cobro_id = $paycoLink->Id;
                $transaction->update();

                $cobroLogTransaction = new CobroLogTransaccion();
                $cobroLogTransaction->transaction_id = $transaction->Id;
                $cobroLogTransaction->cobro_id = $paycoLink->Id;
                $cobroLogTransaction->created_at = new DateTime('now');
                $cobroLogTransaction->updated_at = new DateTime('now');
                $cobroLogTransaction->save();

                $cobroLogTransaction->disponibles = $paycoLink->disponible;
                $cobroLogTransaction->cantidad = $data['quantity'];
                $resta = $cobroLogTransaction->disponibles - $cobroLogTransaction->cantidad;
                $cobroLogTransaction->total_disponibles = $resta <= 0 ? 0 : $resta;
                $cobroLogTransaction->update();
                $paycoLink->disponible = $cobroLogTransaction->total_disponibles;
                $paycoLink->update();
                $this->textResponse = 'PaycoLink Payment Created And Discounted';
            }

            $this->setPaycoLinkStatus($paycoLink);
        } catch (Exception $exception) {

            $this->success = false;
            $this->titleResponse = 'Error';
            $this->textResponse = "Error inesperado al consultar las transacciones con los parametros datos";

            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $this->data = array('totalErrors' => $validate->totalerrors, 'errors' => $validate->errorMessage);
        }

        $arrResponse['success'] = $this->success;
        $arrResponse['titleResponse'] = $this->titleResponse;
        $arrResponse['textResponse'] = $this->textResponse;
        $arrResponse['lastAction'] = $this->lastAction;
        $arrResponse['data'] = $this->data;

        return $arrResponse;
    }

    private function discountPayment($transaction)
    {
        $cliente = $transaction->cliente()->first();

        if ($cliente->fase_integracion == 2) {
            return $transaction->isProductionAccepted() || $transaction->estado == 'Pendiente';
        } else {
            return $transaction->estado == 'Aceptada' || $transaction->estado == 'Pendiente';
        }
    }

    private function setPaycoLinkStatus($paycoLink) {
        $pendingTransactions = $paycoLink->getPendingTransactions();

        if ($paycoLink->disponible == 0 && $pendingTransactions <= 0){
            $paycoLink->estado = 2;
        } elseif ($paycoLink->disponible == 0 && $pendingTransactions > 0) {
            $paycoLink->estado = 3;
        } elseif ($paycoLink->disponible > 0) {
            $paycoLink->estado = 1;
        }
        $paycoLink->update();
    }

    private function existsCobroLogTransaction($data){
        return  CobroLogTransaccion::where([
                [ 'transaction_id', '=', $data["transaction"] ],
                [ 'cobro_id', '=' , $data["paycoLinkId"]],
            ])->exists();
    }
}
