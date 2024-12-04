<?php namespace App\Listeners;
use App\Events\ValidacionesGeneralesApiConsultaMovimientoEvent;
use App\Models\Paises as Paises;
use App\Models\Trm;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
class ValidacionesGeneralesApiConsultaMovimientoListener extends HelperPago
{
    public $request;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->request = $request;
    }
    /**
     * Handle the event.
     *
     * @param ValidacionesGeneralesApiConsultaMovimientoEvent $event
     * @return void
     */
    public function handle(ValidacionesGeneralesApiConsultaMovimientoEvent $event)
    {


        $comercio = $event->arr_parametros['comercio'];
        $ip = $event->arr_parametros['ip'];
        $LLavesclientes = $event->arr_parametros['LLavesclientes'];

        $this->private_key = $event->arr_parametros['private_key'];
        // Buscar el comercio de nuevo dentro de los clientes
        if (!is_object($comercio)) {
            return array(
                'success' => 'false',
                'title_response' => 'Error',
                'text_response' => 'Comercio no existe',
                'last_action' => 'valid_cliente',
                'data' => array()
            );
        }
        $this->saveLog(1, $LLavesclientes->cliente_id,$this->request->all(),"","apiConsultaTransaccion");

        //validar IP que este autorizada

        $ipValida = $this->validarIp($ip,$LLavesclientes);
        if(!$ipValida){

            $response=array(
                'success'         => false,
                'title_response' => 'IP no autorizada',
                'text_response'  => 'La IP desde que se esta haciendo al consulta no esta autorizada para consultar',
                'last_action'    => 'Valida ip autorizada',
                'data'           => array(),
            );
            return $response;

        }

        //validamos variables de la peticion
        if($this->request->has('refPayco')) {
            $refPayco = $this->request->refPayco;
        }

        //validamos variables de la peticion
        if(isset($event->arr_parametros['idCliente'])) {
            $idCliente = $event->arr_parametros['idCliente'];
        }

        if($this->request->has('fechaInicio')) {
            $fechaInicio = $this->request->fechaInicio;
        }

        if($this->request->has('fechaFin')) {
            $fechaFin = $this->request->fechaFin;
        }

        if($this->request->has('tipoMovimiento')) {
            $tipoMovimiento = $this->request->tipoMovimiento;
        }
        if($this->request->has('resultadosPagina')) {
            $resultadosPagina = $this->request->resultadosPagina;
        }
        if($this->request->has('pagina')) {
            $pagina = $this->request->pagina;
        }

        // validamos que si vienen seteadas no esten vacias
        $validate=new Validate();

        if(isset($refPayco)){
            $vrefPayco = $validate->ValidateVacio($refPayco, 'refPayco');
            if (!$vrefPayco) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else{
                $arr_respuesta['refPayco'] = $refPayco;
            }
        }

        if(isset($idCliente)){
            $vidCliente = $validate->ValidateVacio($idCliente, 'idCliente');
            if (!$vidCliente ) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['idCliente'] = $idCliente;
            }
        }

        if(isset($fechaInicio)){
            $vfechaInicio = $validate->ValidateVacio($fechaInicio, 'fechaInicio');
            if (!$vfechaInicio ) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['fechaInicio'] = $fechaInicio;
            }
        }

        if(isset($fechaFin)){
            $vfechaFin = $validate->ValidateVacio($fechaFin, 'fechaFin');
            if (!$vfechaFin ) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['fechaFin'] = $fechaFin;
            }
        }

        if(isset($tipoMovimiento)){
            $vtipoMovimiento = $validate->ValidateVacio($tipoMovimiento, 'tipoMovimiento');
            if (!$vtipoMovimiento) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['tipoMovimiento'] = $tipoMovimiento;
            }
        }

        if(isset($pagina)){
            $vpagina= $validate->ValidateVacio($pagina, 'pagina');
            if (!$vpagina) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['pagina'] = $pagina;
            }
        }
        if(isset($resultadosPagina)){
            $vresultadosPagina= $validate->ValidateVacio($resultadosPagina, 'resultadosPagina');
            if (!$vresultadosPagina) {
                $error = $this->getErrorCheckout('E028');
                $validate->setError($error->error_code, $error->error_message);
            } else {
                $arr_respuesta['resultadosPagina'] = $resultadosPagina;
            }
        }


        if( $validate->totalerrors > 0 ){
            $success         = false;
            $last_action    = 'validacion comercio y datos del filtro';
            $title_response = 'Error';
            $text_response  = 'Algunos campos son erroneos, por favor corrija los errores y vuelva a intentarlo';

            $data           =
                array('totalerrores'=>$validate->totalerrors,
                    'errores'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'title_response' => $title_response,
                'text_response'  => $text_response,
                'last_action'    => $last_action,
                'data'           => $data
            );
            //dd($response);
            $this->saveLog(2,$LLavesclientes->cliente_id, '', $response,'api_consulta_movimiento');

            return $response;
        }

        $arr_respuesta['success'] = true;
        $arr_respuesta['comercio'] = $comercio;

        return $arr_respuesta;
    }
}