<?php

namespace App\Listeners\Buttons\Process;

use App\Events\Buttons\Process\ConsultButtonShowEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BotonesPago;
use App\Models\LlavesClientes;
use App\Models\Transacciones;
use Illuminate\Http\Request;

class ConsultButtonShowListener extends HelperPago
{
    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultButtonShowEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $id = isset($filters->id) ? $filters->id : "";

            $buttonList = BotonesPago::where("id_cliente", $clientId)
                ->where("Id", intval($id))
                ->get()->first();

            $llavesCliente = LlavesClientes::where("cliente_id", $clientId)
                ->get()->first();

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = [
                'id' => $buttonList->Id,
                'detalle' => $buttonList->descripcion,
                'referencia' =>$buttonList->referencia,
                'moneda' => $buttonList->moneda,
                'valor' => $buttonList->valor,
                'tax' => $buttonList->tax,
                'ico' => $buttonList->ico,
                'amount_base' => $buttonList->amount_base,
                'url_respuesta' => $buttonList->url_respuesta,
                'url_confirmacion' => $buttonList->url_confirmacion,
                'url_imagen' => $buttonList->url_imagen,
                'url_imagenexterna' => $buttonList->url_imagenexterna,
                'tipo' => $buttonList->tipo,
                'key' => $llavesCliente->public_key,
            ];

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los cobros con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}