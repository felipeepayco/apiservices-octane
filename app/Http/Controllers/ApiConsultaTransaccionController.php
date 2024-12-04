<?php namespace App\Http\Controllers;

use App\Events\ApiConsultaTransaccionEvent;
use App\Http\Validation\Validate;
use App\Helpers\Pago\HelperPago;
use App\Models\LogRest;
use Illuminate\Http\Request;
use \App\Models\Transacciones;
use \App\Models\DetalleTransacciones;
use \App\Models\DetalleClientes;
use App\Http\Lib\Utils as Utils;
use App\Http\Lib\DescryptObject as DescryptObject;
use App\Models\LlavesClientes;
use Illuminate\Support\Facades\DB;


class ApiConsultaTransaccionController extends HelperPago {

    private $arr_parametros;
    public function __construct(Request $request) {
        parent::__construct($request);
        //$this->middleware('oauth');
        $this->arr_parametros = array(
            'success' => false,
            'title_response'=> 'Error',
            'text_response' => 'Comercio no existe',
            'last_action' => 'valid_cliente',
        );

        $jsonContent = $request->getContent();
        if(is_object($jsonContent)){
            $this->content = json_decode($jsonContent);
            $this->publickey = $this->content->public_key;
        } else if($request->has('public_key')){
            $this->publickey = $request->get('public_key');
        }
    }
    

    public function saveLog($tipo, $clienteId, $request = "", $response = "", $accion = "") {
        $id = uniqid('', true);
        $util = new Utils();
//        if ($tipo == 1) {
//            $_SESSION["logid"] = $id;
            $log = new LogRest();
            $log->session_id = $id;
            $log->cliente_id = $clienteId;
            $log->fechainicio = new \DateTime('now');
            //Pregunta: ¿setear fechafin tambien?
            //$log->fechafin = (new \DateTime())->format('Y-m-d H:i:s');
            $log->request = json_encode($request);
            $log->microtime = $util->microtime_float();
            $log->ip = $util->getRealIP();
            if ($accion != "") {
                $log->accion = $accion;
            }
            $log->save();
//        } else {
//            //***cambiar
//            $log = @LogRest::where('session_id',$_SESSION["logid"])->first();
//            //
//            if ($log) {
//                $log->fechafin = new \DateTime('now');
//                $microfin = $util->microtime_float();
//                $totalmicro = $microfin - $log->microtime;
//                $log->microtime = $totalmicro;
//                $log->response = json_encode($response);
//                $log->save();
//                unset($_SESSION['logid']);
//            }
//        }
    }
}
