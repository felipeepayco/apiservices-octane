<?php namespace app\Listeners;

use App\Events\ApiConsultaMovimientoEvent;
use App\Models\Movimientos as Movimientos;
use App\Models\DetalleClientes as DetalleClientes;
use App\Models\DetalleTransacciones as DetalleTransacciones;
use App\Http\Lib\Utils as Utils;
use App\Helpers\Pago\HelperPago;
use \Illuminate\Http\Request;
use App\Http\Validation\Validate as Validate;


class ApiConsultaMovimientoListener extends HelperPago {
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function handle(ApiConsultaMovimientoEvent $event) {

        // valores iniciales


        //parametros entrantes
        $paramsValidados = $event->arr_parametros;
        if(!isset($event->arr_parametros['comercio'])){
            return array(
                'success' => 'false',
                'title_response' => 'Error',
                'text_response' => 'Direccion IP no autorizada desde el panel de control',
                'last_action' => 'valid_ip',
                'data' => array()
            );
        }
        $comercio = $event->arr_parametros['comercio'];
        $idComercio = $event->arr_parametros['idCliente'];

        //VALIDAMOS FECHAS (POSIBLES DE CONSULTA) DANDO PRIORIDAD EN CASO DE INCONSISTENCIAS COMO AMBOS FILTROS SETEADOS A FEHCA DE TRANSACCION.
        if(isset($paramsValidados['fechaInicio'])){
            $fechaInicio = $paramsValidados['fechaInicio'];
            $fechaFin = $paramsValidados['fechaFin'];


        }else {

            $fechaMesAnterior = new \DateTime('now');
            $fechaMesAnterior = $fechaMesAnterior->modify('-1 month');
            $fechaInicio = $fechaMesAnterior->format('Y-m-d');
            $fechaFin = new \DateTime('now');
            $fechaFin = $fechaFin->format('Y-m-d');
        }
        if(isset($paramsValidados['refPayco'])){
            $refPayco = (integer)$paramsValidados['refPayco'];
        } else {
            $refPayco = false ;
        }
        if(isset($paramsValidados['tipoMovimiento'])){
            $tipoMovimiento = $paramsValidados['tipoMovimiento'];
        }else {
            $tipoMovimiento = false ;
        }
        if(isset($paramsValidados['resultadosPagina'])){
            $resultadosPagina = (integer)$paramsValidados['resultadosPagina'];
        }else {
            $resultadosPagina = 50 ;
        }
        if(isset($paramsValidados['pagina'])){
            $pagina = $paramsValidados['pagina'];
        }else {
            $pagina = 0 ;
        }

        try {

            $movimientos = Movimientos::where(function($query) use($idComercio,$refPayco,$fechaInicio,$fechaFin,$tipoMovimiento){
                $query->where('id_cliente', $idComercio);

                if($fechaInicio){
                    $query->whereBetween('fecha',[$fechaInicio,$fechaFin]);
                }

                if($refPayco){
                    $query->where('idregistro',$refPayco);
                }
                //logica tipo de movimiento
                if($tipoMovimiento){
                    if($tipoMovimiento == 'r' ){
                        $query->whereIn('tipomovimiento',array(5,22,23,29,30));
                    }else{
                        $query->whereNotIn('tipomovimiento',array(5,22,23,29,30));
                    }
                }
            })->paginate($resultadosPagina,['*'], 'pagina', $pagina);

            $success= true;
            $title_response = 'Consulta realizada';
            $text_response = 'Consulta realizada exitosamente';
            $last_action = 'informacion_consultada';
            $data = $movimientos;


        } catch (\Exception $ex){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los movimientos con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }


        $arr_respuesta['success'] = $success;
        $arr_respuesta['title_response'] = $title_response;
        $arr_respuesta['text_response'] = $text_response;
        $arr_respuesta['last_action'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
