<?php namespace app\Listeners;

use App\Events\GetKeysEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Lib\DescryptObject;
use \Illuminate\Http\Request;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use App\Models\BblClientesPasarelas;
use App\Models\Clientes;
use App\Models\DetalleConfClientes;
use App\Models\LlavesClientes;
use App\Models\PasarelaConfig;

class GetKeysListener extends HelperPago
{

    public function handle(GetKeysEvent $event)
    {

        try {
            $event = $event->arr_parametros;
            $id = $event["id"];
            
            $cliente=BblClientes::where("id",$id)->first();
            if(!$cliente){
                return [
                    'success' => false,
                    'titleResponse' => "error",
                    'textResponse' => "Client not found",
                    'lastAction' => "Query Client",
                    'data' => [],
                ];
            }

            $arApiKey=BblClientesPasarelas::where('cliente_id',$id)->where('estado', true)->first();
            
            if (!$arApiKey) {
                return response()->json([
                    'success' => false,
                    'titleResponse' => "error",
                    'textResponse' => "Client Keys not found",
                    'lastAction' => "Query Client keys",
                    'data' => [],
                ]);
            }
            
            $private_key = $arApiKey->private_key;
            $data = array(
                "p_cust_id_cliente" => $arApiKey->cliente_id, 
                'p_key' => $cliente->p_key,
                "privateKey"=>$private_key,
                "publicKey"=>$arApiKey->public_key
            );

            return [
                'success' => true,
                'titleResponse' => "Success",
                'textResponse' => "query successfully",
                'lastAction' => "Query Client",
                'data' => $data,
            ];
        } catch (\Exception $ex) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los movimientos con los parametros datos";
            $last_action = 'fetch data from database';

        }

        $arr_respuesta = [];
        $arr_respuesta['success'] = $success;
        $arr_respuesta['title_response'] = $title_response;
        $arr_respuesta['text_response'] = $text_response;
        $arr_respuesta['last_action'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
