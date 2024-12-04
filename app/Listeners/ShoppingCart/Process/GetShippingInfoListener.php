<?php

namespace App\Listeners\ShoppingCart\Process;

use \Illuminate\Http\Request;
use App\Events\ShoppingCart\Process\GetShippingInfoEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientesInfoPagoEnvio;
use Exception;

class GetShippingInfoListener extends HelperPago
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

    public function handle(GetShippingInfoEvent $event)
    {
        try {
            $fieldValidation    = $event->arr_parametros;
            $email              = $fieldValidation["email"];
            $catalogueId        = $fieldValidation["catalogueId"];

            $bblClientesInfoPagoEnvio=BblClientesInfoPagoEnvio::where("catalogo_id",$catalogueId)->where("email",$email)->first();
            if($bblClientesInfoPagoEnvio){
                $data=[
                    'firstName' =>$bblClientesInfoPagoEnvio->nombre,
                    'lastName'  => $bblClientesInfoPagoEnvio->apellido,
                    'documentId' =>$bblClientesInfoPagoEnvio->document_number,
                    'email' =>$bblClientesInfoPagoEnvio->email,
                    'phone' =>$bblClientesInfoPagoEnvio->telefono,
                    'address1' =>$bblClientesInfoPagoEnvio->direccion,
                    'address2' =>$bblClientesInfoPagoEnvio->otros,
                    'city' => $bblClientesInfoPagoEnvio->ciudad,
                    'codeDane' => $bblClientesInfoPagoEnvio->codeDane,
                    'departament' => $bblClientesInfoPagoEnvio->region,
                    'country' => $bblClientesInfoPagoEnvio->pais,
                ];
                $success = true;
                $title_response = 'Get shipping info';
                $text_response = 'Get shipping info';
                $last_action = 'get_shoppingcart_shipping_info'; 
            }else{
                $success = false;
                $title_response = 'Not found shipping data';
                $text_response = 'Not found shipping data';
                $last_action = 'get_shoppingcart_shipping_info';
                $data = [];               
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get data shipping info";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = ['totalErrors' => $validate->totalerrors, 'errors' =>
            $validate->errorMessage, 'aditionalData'=>$exception];
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}
