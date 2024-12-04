<?php
namespace App\Listeners;


use App\Events\ConsultSellDeleteEvent;
use App\Events\ConsultSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Cobros;
use Illuminate\Http\Request;

class ConsultSellDeleteListener extends HelperPago {

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultSellDeleteEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $id=$fieldValidation["id"];


            $sellDelete= Cobros::where("cliente_id",$clientId)
                ->where("estado",">=",1)
                ->where("id", "{$id}%")
                ->update(["estado"=>0]);

            if($sellDelete){
                $success= true;
                $title_response = 'Successful delete sell';
                $text_response = 'successful delete sell';
                $last_action = 'delete sell';
                $data = [];
            }else{
                $success= false;
                $title_response = 'Error delete sell';
                $text_response = 'Error delete sell, sell not found';
                $last_action = 'delete sell';
                $data = [];
            }



        }catch (\Exception $exception){
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