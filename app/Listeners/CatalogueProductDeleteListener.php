<?php
namespace App\Listeners;


use App\Events\CatalogueProductDeleteEvent;
use App\Events\ConsultSellListEvent;
use App\Events\CatalogueProductNewEvent;
use App\Events\ValidationGeneralSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use App\Models\CatalogoProductos;
use App\Models\CatalogoCategorias;
use App\Models\CatalogoProductosCategorias;

use App\Models\CompartirCobro;
use App\Models\FilesCobro;
use App\Models\Trm;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use Illuminate\Http\Request;

class CatalogueProductDeleteListener extends HelperPago {

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
    public function handle(CatalogueProductDeleteEvent $event)
    {
       
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $id=$fieldValidation["id"];

            $sellDelete= CatalogoProductos::where("cliente_id","=",$clientId)
                ->where("estado","=","1")
                ->where("id", $id)
                ->update(["estado"=>0]);


            if($sellDelete){
                $success= true;
                $title_response = 'Successful delete catalogue product';
                $text_response = 'Successful delete catalogue product';
                $last_action = 'Successful delete catalogue product';
                $data = [];
            }else{
                $success= false;
                $title_response = 'Can not delete catalogue product';
                $text_response = 'Can not delete catalogue product';
                $last_action = 'Can not delete catalogue product';
                $data = [];
            }

        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error delete product";
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