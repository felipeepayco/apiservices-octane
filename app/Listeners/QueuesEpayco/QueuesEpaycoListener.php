<?php

namespace App\Listeners\QueuesEpayco;


use App\Events\QueuesEpayco\QueuesEpaycoEvent;
use App\Models\LlavesClientes;
use Epayco\Colas\Colas;


class QueuesEpaycoListener
{
    private $private_key;
    private $public_key;
    private $clientId;

    public function handle(QueuesEpaycoEvent $event)
    {

        $this->clientId = env("CLIENT_ID_APIFY_PRIVATE");
        $keyClientEpayco = LlavesClientes::where("cliente_id", $this->clientId)->first();

        $this->private_key = $keyClientEpayco->private_key_decrypt;
        $this->public_key = $keyClientEpayco->public_key;

        $data = $event->arr_parametros;

        $queue = isset($data["queue"]) ? $data["queue"] : "movimientos";
        $action = $data["action"];
        $actionId = $data["actionId"];


        return $this->createQueue($queue, $action, $actionId);
    }

    public function createQueue($queue, $action, $actionId)
    {
        try {
            $queus = new Colas($this->public_key, $this->private_key);
            if ($queus instanceof Colas) {
                $addCola = $queus->addMessage($queue, $action, $actionId);
            } else {
                $addCola = new \stdClass();
                $addCola->success = false;
                $addCola->error_code = 500;
                $addCola->error_message = "No se logro crear la cola";
                $addCola->data = "";
            }

            return $addCola;

        } catch (\Exception $exception) {
            $addCola = new \stdClass();
            $addCola->success = false;
            $addCola->error_code = 500;
            $addCola->error_message = $exception->getMessage();
            $addCola->data = "";
            return $addCola;
        }
    }
}