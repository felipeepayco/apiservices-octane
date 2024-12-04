<?php

namespace App\Http\Controllers;

use App\Models\ConfPais;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Http\Validation\Validate;
use App\Helpers\Pago\HelperPago;

use App\Events\ValidarComercioEvent;
use \App\Models\DetalleClientes;
use \App\Models\DetalleConfClientes;

use \App\Models\Clientes;
use \App\Models\ContactosClientes;
use \App\Models\Municipios;
use \App\Models\Departamentos;
use \App\Models\Paises;
use \App\Models\ComisionClienteAliado;
use \App\Models\Paisesindicativos;
use \App\Models\MediosPago;
use \App\Models\PlanFijoClientes;
use \App\Models\PlanesClientes;
use \App\Models\MediosPagoClientes;
use \App\Models\MediosPagoTarifafijaClientes;
use \App\Models\MediosPagoTarifafija;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use App\Models\WsFiltros;
use \App\Models\ConfigPlanFijo;
use App\Models\LimPerfilesValidacion;
use App\Models\LimClientesValidacion;
use App\Models\DocumentosLegales;
use App\Models\TipoDocumentos;
use App\Models\CuentasBancarias;

use App\Http\Lib\Utils as Utils;
use App\Http\Lib\DescryptObject as DescryptObject;


class ClienteController extends HelperPago
{


    public $Request;
    public $p_key;
    public $prod = false;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new controller instance.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getDepartamentos(Request $request)
    {

        $listdepartamentos = DB::table("departamentos")->get();
        $departamentos = array();

        foreach ($listdepartamentos as $row) {
            $departamentos[] = array("id" => $row->id, 'name' => $row->nombre, 'indicative' => $row->indicativo);
        }

        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "department list success",
            'lastAction' => "Query department",
            'data' => $departamentos,
        );

    }

    public function getCiudades(Request $request)
    {


        $department = $request->get("department", "");

        if ($department) {
            $listciudades = DB::table("municipios")->where('id_departamento', $department)->get();
        } else {
            $listciudades = DB::table("municipios")->get();
        }
        $ciudades = array();

        foreach ($listciudades as $row) {
            $ciudades[] = array("id" => $row->id, 'name' => $row->nombre, "department" => $row->id_departamento, 'indicative' => $row->indicativo);
        }

        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "city list sucess",
            'lastAction' => "query city",
            'data' => $ciudades,
        );

    }

    public function getCategorias(Request $request)
    {
        $listcategorias = DB::table("categorias")->get();
        $categorias = array();
        foreach ($listcategorias as $row) {
            $categorias[] = array("id" => $row->id, 'name' => $row->nombre);
        }
        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "category list success",
            'lastAction' => "query category",
            'data' => $categorias,
        );
    }

    public function getNomenclature(Request $request)
    {
        $listNomenclatura = DB::table("direcciones_nomenclatura")->get();
        $nomenclaturas = array();
        foreach ($listNomenclatura as $row) {
            $nomenclaturas[] = array("id" => $row->id, 'name' => $row->nombre, "abbreviation" => $row->abreviatura);
        }
        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "nomenclature list success",
            'lastAction' => "query nomenclature",
            'data' => $nomenclaturas,
        );
    }





    public function emailEnviar($subject, $from, $to, $body, $arrayvals)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->renderView($body, $arrayvals), 'text/html');

        $this->get('mailer')->send($message);
    }

    public function agregarPlanAgregador($cliente_id, $plan)
    {

        if ($plan == "estandar") {
            $plan_id = 1;
        } else {
            $plan_id = 2;
        }

        $planConf = ConfigPlanFijo::find($plan_id);


        if ($planConf) {

            //comisiones configuradas del plan
            $comfranquicias = $planConf->comision_franquicias;
            $comdebito = $planConf->comision_franquicias;
            $comcredito = $planConf->comision_tr_credito;
            $compresencial = $planConf->comision_tr_presencial;
            $comretiro = $planConf->comision_retiro;
            $mensualidad = $planConf->valor_mensual;

            //se configura el plan y queda en estado pendiente
            $plancli = new PlanFijoClientes();
            $plancli->cliente_id = ($cliente_id);
            $plancli->comision_franquicias = ($comfranquicias);
            $plancli->comision_tr_credito = ($comcredito);
            $plancli->comision_tr_pse = ($comdebito);
            $plancli->comision_tr_presencial = ($compresencial);
            $plancli->comision_retiro = ($comretiro);
            $plancli->valor_mensual = ($mensualidad);
            $plancli->estado = (2);
            $plancli->save();
            //se agrega la solicitud del plan en estado pendiente por activar
            $planNuevo = new PlanesClientes();

            $planNuevo->fecha_solicitud = date("Y-m-d H:i:s");
            $planNuevo->cliente_id = ($cliente_id);
            $planNuevo->plan_id = ($plancli->id);
            $planNuevo->estado = (2);
            $planNuevo->save();
            //se agregan las comisiones según el plan seleccionado
            $this->insertarComisionesAgregador($cliente_id, $comfranquicias, $comdebito, $comcredito, $compresencial, $comretiro);

            $success = true;
            $title_response = "OK";
            $text_response = "Plan registrado con exito.";

        } else {

            $success = false;
            $title_response = "Error";
            $text_response = "Plan no encontrado";

        }

        return array(
            'success' => $success,
            'title_response' => $title_response,
            'text_response' => $text_response,

        );

    }

    private function insertConfiguracion($cliente)
    {


        if ($cliente->telefono != "") {
            $telefono = $cliente->telefono;
        }

        if ($cliente->celular != "") {
            $telefono = $cliente->celular;
        }

        $ind_pais = $cliente->ind_pais;
        $cod_pais = $cliente->id_pais;
        $email = $cliente->email;

        if (empty($ind_pais)) {
            $ind_pais = "";
        }
        if (empty($cod_pais)) {
            $cod_pais = "CO";
        }

        $clientCountry = ConfPais::select("conf_pais.id", "conf_moneda.cod_moneda","conf_moneda.id as confMonedaId")
            ->join("pais_moneda", "pais_moneda.conf_pais_id", "=", "conf_pais.id")
            ->join("conf_moneda", "conf_moneda.id", "=", "pais_moneda.conf_moneda_id")
            ->where("conf_pais.cod_pais", $cod_pais)
            ->where("pais_moneda.principal", 1)
            ->first();

        $strtelefono = json_encode(array('cod_pais' => $cod_pais, 'ind_pais' => $ind_pais, 'ind_ciudad' => "", 'telefono' => $telefono));
        $valores = array(
            "9" => $strtelefono,
            "2" => $email,
            "3" => $email,
            "4" => $email,
            "14" => $email,
            "15" => $clientCountry->cod_moneda,
            "16" => "ES",
            "17" => "0",
            "54"=>$clientCountry->id,
            "57"=>$clientCountry->confMonedaId);

        foreach ($valores as $key => $value) {

            $detconf = new DetalleConfClientes();
            $detconf->cliente_id = $cliente->Id;
            $detconf->config_id = ($key);
            $detconf->valor = ($value);
            $detconf->save();
        }
        //Insertamos las llaves publicas


        if ($this->prod) {
            //Generar Codigo Qr
            $url = "https://secure.payco.co/restpagos/key/cliente.json?id=$cliente->Id";
            $data = json_decode(file_get_contents($url));

            //Insertar llaves
            $email = $cliente->email;
            $password = $this->Request->input('password');
            $idcliente = $cliente->Id;
            $bs_password = base64_encode($password);

            $url_qr = "https://secure.payco.co/apprest/qrs/$cliente->Id.json";
            file_get_contents($url_qr);

            //Enviar email de Creación de Cuenta
            $url_validacion_email = "https://secure.payco.co/apprest/email/registro/wobiz?id_cliente=$cliente->Id&password=$bs_password";
            file_get_contents($url_validacion_email);

            try {
                $url_user = "https://dashboard.epayco.co/old/clientes/user/newuser/rest?email=$email&password=$password&clienteid=$idcliente";
                @$data = file_get_contents($url_user);

            } catch (Exception $ex) {
                
            }
        }

    }


    private function insertarComisionesAgregador($cliente_id, $comision_franquicia, $comdebito, $comcredito, $compresencial, $comretiro)
    {

        $dc = new DetalleClientes();

        $comisiontransaccion = $comcredito;
        $comisiontransaccion_pse = $comcredito;
        $comisiontrPrencial = $compresencial;
        $comisionretiro = $comretiro;

        $comisionvisa = $comision_franquicia;
        $comisionmaster = $comision_franquicia;
        $comisionamerican = $comision_franquicia;
        $comisiondiners = $comision_franquicia;
        $comisionpse = $comdebito;
        $comisionPresencial = $comision_franquicia;

        $dc->id_cliente = ($cliente_id);
        $dc->comisionvisa = ($comisionvisa);
        $dc->comisionmaster = ($comisionmaster);
        $dc->comisionamerican = ($comisionamerican);
        $dc->comisiondiners = ($comisiondiners);
        $dc->comision_pse = ($comisionpse);
        $dc->comisiontransaccion = ($comisiontransaccion);
        $dc->comision_presencial = ($comisionPresencial);
        $dc->comision_transaccion_presencial = ($comisiontrPrencial);
        $dc->comision_transaccion_pse = ($comisiontransaccion_pse);
        $dc->comision_retiro = ($comisionretiro);

        $dc->saldo_cliente = (0);
        $dc->saldo_disponible = (0);
        $dc->saldo_reserva = (0);
        $dc->saldo_retenido = (0);
        $dc->porcentaje_reserva = (0);
        $dc->tipocuenta = (1);
        $dc->banco = (0);
        $dc->titular = ("");
        $dc->ncuenta = ('0000000000');
        $dc->save();

    }

    public function insertarValidacionCuenta($clienteId, $perfilId, $num_validaciones)
    {


        for ($index = 1; $index < $num_validaciones; $index++) {
            //consultar ls validaciones del cliente
            $limperfiles = LimPerfilesValidacion::where("validacion_id", $index)->where("perfil_id", $perfilId)->get()->first();
            $limcliente = new LimClientesValidacion();
            $estado_id = 1;

            if (is_object($limperfiles)) {
                $porcentaje = $limperfiles->porcentaje;
            } else {
                $porcentaje = 0;
            }

            $limcliente->cliente_id = ($clienteId);
            $limcliente->validacion_id = ($index);
            $limcliente->porcentaje = ($porcentaje);
            $limcliente->estado_id = ($estado_id);
            $limcliente->save();
        }

        //Omitir validacion de contrato, esto se hace ingresando el tipo doc 3
        //y dandole respuesta de omitir validacion
        //$tipo_doc=$em->getRepository('RestBundle:TipoDocumentosLegales','t')->find(3);

        $documentos_legales = new DocumentosLegales();

        $documentos_legales->cliente_id = ($clienteId);
        $documentos_legales->tipo_doc = 3;
        $documentos_legales->fecha_creacion = date("Y-m-d H:i:s");
        $documentos_legales->respuesta_id = (12);
        $documentos_legales->subido = ("NO");
        $documentos_legales->aprobado = ("SI");
        $documentos_legales->save();
    }

    public function insertarFiltros($clienteId, $tipocliente)
    {

        $filtrosdefault = WsFiltrosDefault::where("id", ">", "0")->get();

        foreach ($filtrosdefault as $filtro) {
            //de filtros default a filtros de clientes
            $filtroscli = new WsFiltrosClientes();

            $filtroscli->id_cliente = $clienteId;
            $filtroscli->filtro = $filtro->filtro;
            $filtroscli->estado = $filtro->estado;

            $filtromonto = json_decode($this->filtrosMontos($filtro->filtro, $tipocliente));

            if ($filtromonto->success) {
                if ($filtro->filtro == 1) {
                    $filtroscli->valor = ($filtro->valor);
                } else {
                    $filtroscli->valor = ($filtromonto->valor);
                }

            } else {
                $filtroscli->valor = $filtro->valor;
            }
            $filtroscli->save();

        }

    }

    public function filtrosMontos($fitroId, $tipoCliente)
    {

        $filtrosmonto = DB::table('ws_filtros_montos')->where("tipo_cliente", $tipoCliente)->where("filtro_id", $fitroId)->get()->first();

        if ($filtrosmonto) {

            return json_encode(array('success' => true,
                'filtro' => $filtrosmonto->filtro_id,
                'valor' => $filtrosmonto->valor
            ));
        } else {
            return json_encode(array('success' => false));
        }
    }

    public function insertarMediosPago($clienteId)
    {

        $mediospago = DB::table('medios_pago')->get();

        foreach ($mediospago as $medio) {

            //medios de pagos activados para el cliente
            $mpagocliente = new MediosPagoClientes();
            $mpagocliente->id_cliente = $clienteId;
            $mpagocliente->id_medio = $medio->Id;
            $mpagocliente->comision = 2.68;
            $mpagocliente->valor_comision = $medio->valor_comision_cliente;
            $mpagocliente->estado = 1;
            $mpagocliente->save();

        }

        $cliente = Clientes::find($clienteId);
        $tarifapse = MediosPagoTarifafija::find("PSE");

        $tarifa_clientepse = new MediosPagoTarifafijaClientes();
        $tarifa_clientepse->cliente_id = ($cliente->id);
        $tarifa_clientepse->medio_pago_id = ("PSE");
        $tarifa_clientepse->valormaximo = $tarifapse->valormaximo;
        $tarifa_clientepse->valor_comision_cliente = $tarifapse->valor_comision_cliente;
        $tarifa_clientepse->estado = 1;
        $tarifa_clientepse->save();

    }

    private function getArrCliente($cliente)
    {

        if ($cliente->pin > 0) {
            $nuevo = 0;
        } else {
            $nuevo = 1;
        }

        $entityPais = false;
        $entityCiudad = false;
        $entitydepto = false;

        if ($cliente->id_pais != "") {
            $entityPais = Paises::where('codigo_pais', $cliente->id_pais)->get()->first();
        }
        if ($cliente->id_ciudad != "") {
            $entityCiudad = Municipios::find($cliente->id_ciudad);
        }
        if ($entityCiudad) {
            $entitydepto = Departamentos::find($entityCiudad->id_departamento);
        }
        if ($entityPais) {
            $name_pais = $entityPais->nombre_pais;
        } else {
            $name_pais = 'Sin Pais';
        }
        if ($entityCiudad) {
            $name_ciudad = $entityCiudad->nombre;
            $ciudad_id = $entityCiudad->id;
        } else {
            $name_ciudad = '';
            $ciudad_id = '';
        }
        if ($entitydepto) {
            $departamento_id = $entitydepto->id;
            $departamento_nombre = $entitydepto->nombre;
            $indicativo = $entitydepto->indicativo;
        } else {
            $departamento_id = "";
            $departamento_nombre = '';
            $indicativo = '';
        }

        if ($cliente->fase_integracion == 1) {
            $lafase = "pruebas";
        } else {
            $lafase = "produccion";
        }

        $porc_validacion = $this->getTotalPorcentaje($cliente->Id);

        if ($porc_validacion == "" || $porc_validacion == null) {
            $porc_validacion = 0;
        }
        //celular y e-mail validado
        $celular_validado = false;
        $email_validado = false;

        $emailsms = DB::table("lim_email_sms")->where("cliente_id", $cliente->Id)->get()->first();

        if ($emailsms) {

            //editar validacion de email
            $aprobadoant = json_decode($emailsms->aprobado);
            $email = $aprobadoant->email;
            $sms = $aprobadoant->sms;

            if ($email == "si") {
                $email_validado = true;
            }

            if ($sms == "si") {
                $celular_validado = true;
            }
        }

        //LLaves transaccionalae
        $llaves_publicas = DB::table("llaves_clientes")->where("cliente_id", $cliente->Id)->get()->first();

        if ($llaves_publicas) {
            $public_key = $llaves_publicas->public_key;
            $private_key = $llaves_publicas->private_key_decrypt;
        } else {
            if (!$this->prod) {
                $public_key = "5f7b3f0488557e7f51e4ba55eecb1391";
                $private_key = "e1f91dfd5e175df36a4a15c00220d3b1";
            }
        }


        return array('id' => $cliente->Id,
                'fecha' => date_format($cliente->fecha_creacion, "Y-m-d H:i:s"),
                'tipo_doc' => $this->Request->input("tipo_doc"),
                'documento' => $cliente->documento,
                'nombre' => $cliente->nombre ? $cliente->nombre : $cliente->razon_social,
                'apellido' => $cliente->apellido ? $cliente->apellido : "",
                'nombre_empresa' => $cliente->nombre_empresa,
                'razon_social' => $cliente->razon_social,
                'email' => $cliente->email,
                'celular' => $cliente->celular,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
                'pais' => $name_pais,
                'ciudad' => $name_ciudad,
                'tipo_cliente' => $cliente->tipo_cliente,
                'estado' => "Activo",
                'public_key' => $public_key,
                'private_key' => $private_key,
                'p_key' => $this->p_key,
                'p_cust_id_cliente' => $cliente->Id

            );
    }

    private function encriptar($text)
    {

        $descryptObj = new DescryptObject();
        $imputKey = $this->container->getParameter('imput_key');
        $text_encrypt = $descryptObj->setTextEncript($text, $imputKey);

        return $text_encrypt;
    }

    private function generarQr($id)
    {
        //Generar el qr desde la url de apprest
        // Grabar en la bd la url del qr;
    }

    public function GetClienteEmailOrDocument($email = "", $documento = "", $tipo_cliente)
    {

        $entity = false;

        if ($email != "") {
            $entity = Clientes::where("email", $email)->get()->first();
        }

        if ($documento != "" && $tipo_cliente == "") {
            $entity = Clientes::where("documento", $documento)
                ->where("tipo_cliente", $tipo_cliente)
                ->get()->first();
        }
        return $entity;
    }

    public function emailExisteUpdate($old_email, $new_email)
    {

        $entity = Clientes::where("email", $new_email);

        if ($entity) {

            if ($old_email == $new_email) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function existeFacebookId($facebookid)
    {

        $entity = Clientes::where("fb_user_id", $facebookid)->get()->first();
        if (is_object($entity)) {
            return true;
        } else {
            return false;
        }
    }

    private function getTipoDocumento($codigo)
    {
        if (is_numeric($codigo)) {
            $TipoDoc = TipoDocumentos::find($codigo);
        } else {
            $TipoDoc = TipoDocumentos::where("codigo", $codigo)->get()->first();
        }
        if (is_object($TipoDoc)) {
            return $TipoDoc->id;
        } else {
            return false;
        }

    }

    private function ValidarCedula($p_document, $p_fechaexpedicion)
    {
        return true;
    }

    public function emailExiste($email)
    {

        $data = DB::connection()->select("select clientes.* from clientes where email='$email'");
        $entity = false;
        if (count($data) > 0) {
            $entity = $data[0];
        }
        if (is_object($entity)) {
            return true;
        } else {
            return false;
        }
    }

    public function documentoExiste($documento, $tipo)
    {

        $data = DB::connection()->select("select clientes.* from clientes where tipo_cliente='$tipo' and documento='$documento'");
        $entity = false;
        if (count($data) > 0) {
            $entity = $data[0];
        }
        if (is_object($entity)) {
            return true;
        } else {
            return false;
        }
    }

    public function generarkeyCli($id)
    {

        $str = $this->randomString(10);
        $shaup = sha1($str);
        return $shaup;
    }

    private function getCodCiudad($pais, $nombreciudad)
    {

        if ($pais != 'CO') {
            $resultciudades = Ciudades::where('codigo_pais', $pais)->where('nombre_ciudad', 'like', '%' . $nombreciudad . '%')->get();
        } else {
            $resultciudades = Municipios::where('nombre', 'like', '%' . $nombreciudad . '%')->get();
        }
        $ciudad = "";
        foreach ($resultciudades as $ciudades) {
            $ciudad = $ciudades;
            break;
        }
        if (is_object($ciudad)) {
            return $ciudad;
        } else {
            return "";
        }

    }

    private function getIndicativoRegion($depto)
    {
        $depto = Departamentos::find($depto);
        if (is_object($depto)) {
            return $depto->indicativo . "-";
        } else {
            return "";
        }
    }

    private function getIndicativoPais($pais)
    {

        $objpais = Pais::where('cod_pais', $pais)->get()->first();
        $ind_pais = "";
        if (is_object($objpais)) {
            $objpais->indicativo;
        }
        return $ind_pais;
    }

    private function randomString($length = 6)
    {

        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

    private function getTotalPorcentaje($clienteId)
    {

        //$sql='SELECT sum(porcentaje) as total FROM `lim_clientes_validacion` where cliente_id=8950 and estado_id=1';
        $result = DB::select("SELECT SUM(v.porcentaje) as total FROM lim_clientes_validacion v WHERE v.cliente_id=$clienteId AND v.estado_id=1");
        $total = $result[0]->total;
        if ($total) {
            $total = (int)$total;
        } else {
            $total = 0;
        }
        return $total;
    }

    private function getCuentaDavivenda($tipo_doc, $documento, $numero, $tipo_cuenta)
    {

        $cuenta = false;

        $data = array(
            "tipoIdentificacion" => "$tipo_doc",
            "numeroIdentificacion" => "$documento",
            "reference" => "$numero",
            "accountType" => "$tipo_cuenta"
        );

        $data_string = json_encode($data);
        $ch = curl_init('https://secure.payco.co/restpagos/api/davivienda/product/validation');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        try {

            $result = curl_exec($ch);
            $result = json_decode($result);
            if (is_array($result)) {
                $cuenta = $this->decryptProductNumber($result[0]->productNumber);
            } else {

            }
        } catch (\Exception $ex) {
            $cuenta = false;
        }

        return $cuenta;


    }

    private function geoip()
    {

        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            $url1 = 'https://secure.payco.co/apprest/geoip/json/';
            $file = file_get_contents($url1 . $ip);
            $data = json_decode($file);
        } catch (\Exception $e) {

            $data = NULL;
        }
        return $data;
    }


}
