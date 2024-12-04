<?php namespace App\Http\Controllers;

use App\Events\GetKeysEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Lib\DescryptObject;
use App\Models\Clientes;
use App\Models\ConfClientes;
use App\Models\DetalleConfClientes;
use App\Models\LlavesClientes;
use App\Models\Paises;
use App\Models\PasarelaConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Constraint\IsTrue;

class ConfigurationController extends HelperPago
{

  

    public function getKeys(Request $request){
        try {
            $id=$request->get('clientId');
            

            $dataRequest = ["id" => $id];
          return  $response = event(new GetKeysEvent($dataRequest),$dataRequest);

        } catch (\Exception $e) {
            $response = [];
            $response['success'] = false;
            $response['title_response'] = "error";
            $response['text_response'] = "Server error";
            $response['last_action'] = "query DetailConfClient";
            $response['data'] = $e;
        }
        return response()->json($response);
    }
  
	public function crearRespuesta($datos) {
        return response()->json($datos);
    }

    public function crearRespuestaError($mensaje, $codigo) {
        return response()->json(['message' => $mensaje, 'code' => $codigo], $codigo);
    }
}
