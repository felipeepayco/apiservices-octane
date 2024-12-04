<?php
namespace App\Listeners\Buttons\Process;

use App\Events\Buttons\Process\ConsultButtonDeleteEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BotonesPago;
use Illuminate\Http\Request;

class ConsultButtonDeleteListener extends HelperPago {

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultButtonDeleteEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $id=$fieldValidation["id"];


            $buttonDelete= BotonesPago::where("id_cliente",$clientId)
                ->where("id", "{$id}%")
                ->first();

            if($buttonDelete){
                $buttonDelete->delete();
                $success= true;
                $title_response = 'Successful delete button';
                $text_response = 'successful delete button';
                $last_action = 'delete sell';
                $data = [];
            }else{
                $success= false;
                $title_response = 'Error delete button';
                $text_response = 'Error delete button, button not found';
                $last_action = 'delete sell';
                $data = [];
            }



        }catch (\Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los Botones con los parametros datos";
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