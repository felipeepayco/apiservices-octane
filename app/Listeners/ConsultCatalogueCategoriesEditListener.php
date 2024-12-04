<?php
namespace App\Listeners;


use App\Events\ConsultCatalogueCategoriesEditEvent;
use App\Events\ConsultSellEditEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\CatalogoCategorias;
use App\Models\Cobros;
use Illuminate\Http\Request;

class ConsultCatalogueCategoriesEditListener extends HelperPago {

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
    public function handle(ConsultCatalogueCategoriesEditEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $id=$fieldValidation["id"];

            $sellList= CatalogoCategorias::where("catalogo_categorias.estado",">=",1)
                ->where("catalogo_categorias.id","{$id}")
                ->where("catalogo_categorias.cliente_id","{$clientId}")
//                ->select("c.id as catalogueId")
//                ->addSelect("c.nombre as catalogueName")
                ->select("catalogo_categorias.id as id")
                ->addSelect("catalogo_categorias.nombre as name")
                ->addSelect("catalogo_categorias.fecha as date")
                ->first();

            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $sellList?$sellList:[];


        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar  categoria con los parametros datos";
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