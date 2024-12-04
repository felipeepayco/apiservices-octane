<?php


namespace App\Listeners;


use App\Events\ConsultClientListKeysEvent;
use App\Events\ValidationGeneralClientListKeysEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\LlavesClientes;
use App\Models\BblClientesPasarelas;
use Illuminate\Http\Request;

class ConsultClientListKeysListener extends  HelperPago
{
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
    public function handle(ConsultClientListKeysEvent $event)
    {
        try{
            $clientId = $event->arr_parametros["idClient"];

            
            $clientKeys = BblClientesPasarelas::where('cliente_id',$clientId)->first();
            if(isset($event->arr_parametros["nameCatalogue"])){
                $nombre = $event->arr_parametros["nameCatalogue"] ;
                $clientKeys['nameCatalogue']=$nombre;
            }
            $success = true;
            $title_response = 'Successfully consult client keys';
            $text_response = 'Successfully consult client keys';
            $last_action = 'consult_list_catalogue';
            $data = $clientKeys;

        }catch(\Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
            $last_action = 'consult client keys';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
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