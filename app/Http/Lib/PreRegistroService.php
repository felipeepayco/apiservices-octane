<?php

namespace App\Http\Lib;


use App\Common\FiscalResponsibilityCodes;
use App\Common\ModalConfigId;
use App\Common\ProductosId;
use App\Common\TaxCodes;
use App\Common\TckDepartamentosId;
use App\Common\TckPrioridadId;
use App\Common\TiposPlanId;
use App\Events\Tickets\Process\ProcessCreateTicketEvent;
use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Listeners\Services\ClientProductService;
use App\Listeners\Services\ModalClientesService;
use App\Models\Bancos;
use App\Models\Cities;
use App\Models\Clientes;
use App\Models\ClientesReconocimientoPublico;
use App\Models\ConfAfiliaciones;
use App\Models\ConfigPlanFijo;
use App\Models\ConfPais;
use App\Models\ContactosClientes;
use App\Models\CuentasBancarias;
use App\Models\CuentasBancariasRespuestas;
use App\Models\Departamentos;
use App\Models\DetalleClientes;
use App\Models\DetalleConfClientes;
use App\Models\DetalleFacturasProforma;
use App\Models\DocumentosLegales;
use App\Models\Facturas;
use App\Models\FacturasProforma;
use App\Models\GrantUser;
use App\Models\InspectorRegistro;
use App\Models\LimClientesValidacion;
use App\Models\LimEmailSms;
use App\Models\LimPerfilesValidacion;
use App\Models\MediosPago;
use App\Models\MediosPagoClientes;
use App\Models\MediosPagoTarifafija;
use App\Models\MediosPagoTarifafijaClientes;
use App\Models\Municipios;
use App\Models\Paises;
use App\Models\PlanesClientes;
use App\Models\PlanFijoClientes;
use App\Models\PreRegister;
use App\Models\Regions;
use App\Models\ResponsabilidadFiscal;
use App\Models\ResponsabilidadFiscalClientes;
use App\Models\TipoDocumentos;
use App\Models\TiposCuenta;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use App\Models\WSFiltrosMontos;
use App\Models\UserCuenta;
use App\Models\WsConfiguracionCliente;
use App\Models\WsConfiguracionRegla;
use App\Models\WsPlantilla;
use Exception;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\IFTTTHandler;
use function Doctrine\Common\Cache\Psr6\get;

class PreRegistroService
{

    public $request;

    const COMMERCE = 'C';
    const PERSON = 'P';
    const AGGREGATOR = 1010;
    const GATEWAY = 1011;
    const ACTIVE = 1;

    /**
     * @param $password
     * @return \Exception|mixed
     * Función que consume servicio para crear el usuario.
     */
    public function registerUser($preRegistro, $password)
    {
        $client = $this->setClientPersonOrCommerce($preRegistro, $password);
        if ($client) {
            $success = true;
            $title_response = 'Registro realizado con Ã©xito';
            $text_response = 'Felicidades, te has registrado con Ã©xito';
            $data = $client;
        } else {
            $success = false;
            $title_response = 'Registro Fallido!';
            $text_response = 'Registro Fallido!';
            $data = array();
        }

        return [
            'success' => $success,
            'title_response' => $title_response,
            'text_response' => $text_response,
            'last_action' => 'insert data',
            'data' => $data,
            'typeUser' => 'aggregator',
        ];

        /*$arDatos = array('pais' => $preRegistro->country,
            'depto' => "",
            'ciudad' => "",
            'direccion' => "",
            'celular' => $preRegistro->cel_number,
            'email' => $preRegistro->email,
            'password' => $password,
            'telefono' => "",
            'plan' => 'estandar',
            'tipo_doc' => $preRegistro->doc_type,
            'documento' => $preRegistro->doc_number,
            'nombres' => $preRegistro->names,
            'apellidos' => $preRegistro->surnames,
            'categoria' => $preRegistro->category,
            'subcategoria' => $preRegistro->subcategory,
            "meta_registro" => $preRegistro->meta_tag == "ecommerceDay" ? 6 : 1,
            "alianza_id" => $preRegistro->alianza_id,
        );
        try {


            $jsonData = json_encode($arDatos);
            $url_rest = "{$baseUrlRest}/apprest/";
            $url_crear_cliente = $url_rest . 'personas.json';

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url_crear_cliente,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{$jsonData}",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            $resp = json_decode($resp);
            return $resp;


        } catch (\Exception $exception) {
            return $exception;

        }*/
    }

    /**
     * @param $password
     * @return \Exception|mixed
     * Función que consume servicio para crear el usuario.
     */
    public function registerUserComerce($preRegistro, $password, $register = null, bool $isExpressRegister = false)
    {

        if ($preRegistro->doc_type == 'NIT') {
            $tipo_regimen = 2;
        } else {
            $tipo_regimen = 1;
        }

        $arDatos = array('pais' => $preRegistro->country,
            'depto' => isset($register["department"]) ? $register["department"] : "",
            'ciudad' => isset($register["city"]) ? $register["city"] : "",
            'direccion' => isset($register["address"]) ? $register["address"] : "",
            'celular' => $preRegistro->cel_number,
            'email' => $preRegistro->email,
            'password' => $password,
            'telefono' => isset($register["phone"]) ? $register["phone"] : "",
            'plan' => 'estandar',
            'tipo_doc' => $preRegistro->doc_type,
            'nit' => $preRegistro->doc_number,
            'digito' => $preRegistro->digito,
            'nombre_empresa' => $preRegistro->nombre_empresa,
            'razon_social' => $preRegistro->nombre_empresa,
            'tipo_regimen' => $tipo_regimen,
            'categoria' => $preRegistro->category,
            'subcategoria' => $preRegistro->subcategory,
            "meta_registro" => $preRegistro->meta_tag == "ecommerceDay" ? 6 : 1,
            "alianza_id" => $preRegistro->alianza_id,
            "nombres" => $preRegistro->names,
            "apellidos" => $preRegistro->surnames,
            'slug' => isset((json_decode($preRegistro->request, true))['slug'])
                ? (json_decode($preRegistro->request, true))['slug']
                : null,
            'restrictiveList' => $preRegistro->restricted_user ?? null,


            //campos para gateway
            'celular_legal' => isset($register["legalMobilePhone"]) ? $register["legalMobilePhone"] : "",//obligatorio

            'extension_legal' => isset($register["legalExt"]) ? $register["legalExt"] : "",//obligatorio
            'telefono_legal' => isset($register["legalPhone"]) ? $register["legalPhone"] : "",//obligatorio

            'email_legal' => isset($register["legalMail"]) ? $register["legalMail"] : "",//obligatorio

            'tipo_doc_legal' => isset($register["legalDocType"]) ? $register["legalDocType"] : "", //obligatorio
            'documento_legal' => isset($register["legalDocNumber"]) ? $register["legalDocNumber"] : "", //obligatorio
            'fechaExpedicion' => isset($register["expeditionDate"]) ? $register["expeditionDate"] : "", //obligatorio

            'pagina_web' => isset($register["website"]) ? $register["website"] : "",
            'ciiu' => isset($register["ciiu"]) ? $register["ciiu"] : "",
            'actividad' => isset($register["descriptionSell"]) ? $register["descriptionSell"] : "",

            'banco' => isset($register["bankCode"]) ? $register["bankCode"] : "",
            'tipo_cuenta' => isset($register["bankAccountType"]) ? $register["bankAccountType"] : "",
            'numerocuenta' => isset($register["bankAccountNumber"]) ? $register["bankAccountNumber"] : "",
            'proforma' => $preRegistro->proforma,
            'idEntidadAliada' => isset($preRegistro->id_cliente_entidad_aliada) ? $preRegistro->id_cliente_entidad_aliada : 4877,

            //campos responsabilidad fiscal
            "arrResponsabilidadesFiscales" => isset($register["fiscalResponsibilities"]) ? $register["fiscalResponsibilities"] : [],
            "clasificacionDian" => isset($register["dianClassification"]) ? $register["dianClassification"] : "",
            "clasificacionRegimen" => isset($register["regimeClassification"]) ? $register["regimeClassification"] : "",
            "idIcaRegion" => isset($register["idIcaRegion"]) ? $register["idIcaRegion"] : "",
            "idIcaCiudad" => isset($register["idIcaCity"]) ? $register["idIcaCity"] : "",
            "publicReconigtion" => isset($register["publicRecognition"]) ? $register["publicRecognition"] : "",
            "responsableIva" => isset($register["responsibleTax"]) ? $register["responsibleTax"] : "",
            "email_facturacion" => isset($register["billEmail"]) ? $register["billEmail"] : $preRegistro->email,
            "role" => isset($register["role"]) ? $register["role"] : "",
            "position" => isset($register["position"]) ? $register["position"] : "",
            "startDate" => isset($register["startDate"]) ? $register["startDate"] : "",
            "endDate" => isset($register["endDate"]) ? $register["endDate"] : "",
            "performing" => isset($register["performing"]) ? $register["performing"] : ""
        );

        $isAlliedEntity = $arDatos['idEntidadAliada'] != getenv('CLIENT_ID_APIFY_PRIVATE');

        if ($preRegistro->plan_id == TiposPlanId::GATEWAY && ($isAlliedEntity || $isExpressRegister))  {
            // si es gateway y la entidad aliada es distinto a ePayco sigue por el flujo viejo de gateway
            return $this->gateway($arDatos, $isExpressRegister);
        } else {
            $client = $this->setClientPersonOrCommerce($preRegistro, $password);
            if ($client) {
                $success = true;
                $title_response = 'Registro realizado con Ã©xito';
                $text_response = 'Felicidades, te has registrado con Ã©xito';
                $data = $client;
            } else {
                $success = false;
                $title_response = 'Registro Fallido!';
                $text_response = 'Registro Fallido!';
                $data = array();
            }

            return [
                'success' => $success,
                'title_response' => $title_response,
                'text_response' => $text_response,
                'last_action' => 'insert data',
                'data' => $data,
                'typeUser' => 'aggregator',
            ];
        }
    }

    public function gateway($data, bool $isExpressRegister = false)
    {
        $tipoDocumento = TipoDocumentos::where("codigo", $data["tipo_doc"])->first();
        if ($tipoDocumento) $data["tipo_doc"] = $tipoDocumento->id;

        $urlvalemail = "";
        $depto = $data['depto'];
        $cliente = new Clientes();
        $cliente->fecha_creacion = (new \DateTime('now'));
        $cliente->id_regimen = ($data['tipo_regimen']);
        $cliente->digito = ($data['digito']);
        $cliente->tipo_doc = ($data['tipo_doc']);
        $cliente->documento = ($data['nit']);
        $cliente->nombre = (strtoupper($data['razon_social']));
        $cliente->fecha_creacion = (new \DateTime());
        $cliente->apellido = ("");
        $cliente->razon_social = (strtoupper($data['razon_social']));
        $cliente->nombre_empresa = (strtoupper($data['razon_social']));
        $cliente->id_pais = ($data['pais']);
        $cliente->id_regimen = ($depto);
        $cliente->id_ciudad = ($data['ciudad']);
        $cliente->id_categoria = ($data['categoria']);
        $cliente->id_subcategoria = ($data['subcategoria']);
        $cliente->direccion = ($data['direccion']);
        $cliente->tipo_usuario = (1);
        $cliente->slug = $data['slug'];
        $cliente->restricted_user = $data['restrictiveList'];

        if ($data['telefono'] !== "") {
            $phone = $this->getIndicativoRegion($depto) . $data['telefono'];
        } else {
            $phone = "";
        }

        $celular = filter_var($data['celular'], FILTER_SANITIZE_NUMBER_INT);
        $celular_legal = filter_var($data['celular_legal'], FILTER_SANITIZE_NUMBER_INT);

        $cliente->telefono = ($phone);
        $cliente->id_plan = ('1011');
        $cliente->tipo_cliente = ('C');
        $cliente->perfil_id = ('99');
        $cliente->celular = ($celular);
        $cliente->pagweb = ($data['pagina_web']);
        $cliente->email = ($data['email']);
        $cliente->contrasena = $this->encriptar($cliente->documento);
        $cliente->fase_integracion = (2);
        $cliente->id_estado = (0);
        $cliente->detalle_estado = (0);
        if ($data['ciiu'] != "") {
            $cliente->ciiu = ($data['ciiu']);
        }
        if ($data['actividad'] != "") {
            $cliente->actividad = ($data['actividad']);
            $cliente->servicio = ($data['actividad']);
        }

        $contacto = new ContactosClientes();
        $contacto->tipo_doc = $data['tipo_doc_legal'] !== '' ? $data['tipo_doc_legal'] : $data['tipo_doc'];
        $contacto->documento = $data['documento_legal'] !== '' ? $data['documento_legal'] : $data['nit'];
        $contacto->nombre = strtoupper($data['nombres']);
        $contacto->apellido = strtoupper($data['apellidos']);
        $contacto->telefono = trim($data['telefono_legal'] !== '' ? $data['telefono_legal'] : $celular);
        $contacto->email = $data['email_legal'] !== '' ? $data['email_legal'] : $data['email'];
        $contacto->ext = $data['extension_legal'];
        $contacto->celular = $celular_legal !== '' ? $celular_legal : $celular;
        $contacto->tipo_contacto = ('legal');
        $fecha = $data['fechaExpedicion'];
        if ($fecha !== "") {
            $p_fechaexpedicion = date_format((new \DateTime($fecha)), 'Y-m-d');
            $contacto->fecha_exp = (new \DateTime($p_fechaexpedicion));
        }

        if ($this->emailExiste($data['email'], $data['idEntidadAliada']) && !$isExpressRegister) {

            $last_action = 'validation input data';
            $success = false;
            $title_response = 'Cuenta ya existente.';
            $text_response = 'el email ya se encuentra en uso.';
            $data = array();

        } else {

            $last_action = 'insert data';

            try {
                $this->generarkeyCli($cliente);
//                $cliente->codigo_qr = ($this->generarQr($cliente->Id));
                $cliente->codigo_qr = (null);
                $cliente->save();
                $contacto->id_cliente = ($cliente->Id);
                $contacto->save();

                // se inserta responsabilidades fiscales
                $this->responsabilidadFiscal($cliente->Id, $data);
                $cliente->responsable_iva = $data["responsableIva"];
                $cliente->save();

                //se inserta el log en inspector registro
                $this->inspectorRegistro($cliente);

                //se inserta el detalle del clientes
                $this->InsertarDetalleCliente(
                    $cliente,
                    $data["clasificacionDian"],
                    $data["clasificacionRegimen"],
                    $data["idIcaRegion"],
                    $data["idIcaCiudad"]
                );

                $this->reconocimientoPublico(
                    $cliente,
                    $data["publicReconigtion"],
                    $data["role"],
                    $data["position"],
                    $data["startDate"],
                    $data["endDate"],
                    $data["performing"]
                );

                if ($data['numerocuenta'] != "") {
                    //Agregamos la cuenta bancaria
                    $id_banco = $data['banco'];
                    $tipo_cuenta = $data['tipo_cuenta'];
                    $numero_tarjeta = $data['numerocuenta'];
                    $this->InsertarCuentaBancaria($cliente, $tipo_cuenta, $numero_tarjeta, $id_banco);
                }

                //insertar filtros al cliente
                $this->insertarFiltros($cliente->Id, 'C');
                //insertar medios de pago
                $this->insertarMediosPagoGateway($cliente->Id);
                //insertar validaciones de cuenta
                $this->insertarValidacionCuenta($cliente->Id, $cliente->perfil_id, 6);

                // si es un registro express, no se aumenta la validacion de la cuenta
                if (!$isExpressRegister) {
                    //colocar validacion comprobar identidad OK
                    $this->editarLimites($cliente->Id, 3, 1);

                    //Colocar el paso de contactos legal en estado 2
                    $this->editarLimites($cliente->Id, 2, 2);


                    //Buscamos la validacion numero 1 la pasamos la numero
                    $limCLientes = LimClientesValidacion::where("cliente_id", $cliente->Id)->get();

                    if (count($limCLientes) > 0) {

                        foreach ($limCLientes as $limite) {
                            /**
                             * @var $limite LimClientesValidacion
                             */
                            if ($limite->validacion_id == 1) {
                                $limite->estado_id = (1);
                            }
                            if ($limite->validacion_id == 2) {

                                $codemail = $this->generateString();
                                $codsms = $this->generateString();
                                $emailsms = new LimEmailSms();
                                $emailsms->cliente_id = ($cliente->Id);
                                $aprobado = '{"email":"no","sms":"si"}';
                                $emailsms->aprobado = ($aprobado);
                                $emailsms->codigo_email = ($codemail);
                                $emailsms->cod_sms = ($codsms);
                                $emailsms->save();

                                $cliente->ind_pais = ('57');
                                $cliente->save();

                                $limite->estado_id;
                            }
                            if ($limite->validacion_id == 3) {
                                $limite->estado_id = (1);
                            }
                            if ($limite->validacion_id == 4) {
                                $limite->estado_id = (4);
                            }
                            if ($data['numerocuenta'] != "") {
                                // si no hay cuenta bancaria no se valida este paso
                                if ($limite->validacion_id == 5) {
                                    $limite->estado_id = (1);
                                }
                            }
                            $limite->save();
                        }
                    }
                }

                if ($data["proforma"]) {
                    $confEntityAllied = DetalleConfClientes::where('cliente_id', $data['idEntidadAliada'])
                        ->where('config_id', 50)
                        ->first();
                    if ($confEntityAllied) {
                        $conf = $confEntityAllied->valor;
                        $arrayConf = json_decode($conf, true);
                    }
                    if (isset($arrayConf['proforma']) && $arrayConf['proforma'] === false) {
                        $return = array("success" => true, "title_response" => "Sin proforma", "text_response" => "Registro creado sin proforma", "data" => ["proforma" => 0, "urlProforma" => "https://dashboard.epayco.co/dashboard"]);
                    } else {
                        $return = $this->PagoAfiliacionGatewayAction($cliente->Id);
                    }
                } else {
                    $return = array("success" => true, "title_response" => "Sin proforma", "text_response" => "Registro creado sin proforma", "data" => ["proforma" => 0, "urlProforma" => "https://dashboard.epayco.co/dashboard"]);
                }

                $success = true;
                $title_response = 'Registro realizado con Ã©xito';
                $text_response = 'Felicidades, te has registrado con Ã©xito y estas a un paso de ser un usuario ePayco. Se ha enviado un mensaje a tu correo electrÃ³nico para que confirmes tu cuenta y hagas parte de ePayco Comercios Gateway.';
                $personaobj = (object)$cliente;
                $contactobj = (object)$contacto;
                //insertar configuracion cliente
                $clientCountryData = $this->getClientCountryData($cliente->id_pais);
                $this->insertConfiguracion($cliente, $data["email_facturacion"], true,$clientCountryData);

                $data = array('comercio' => $personaobj, 'contacto' => $contactobj, "proforma" => $return["success"], "data" => $return["data"]);

                $urlvalemail = $cliente->key_cli;

            } catch (DBALException $exc) {

                $success = false;
                $title_response = 'Registro Fallido!';
                $text_response = $exc->getMessage();
                $data = array();
            }
        }

        return array(
            'success' => $success,
            'title_response' => $title_response,
            'text_response' => $text_response,
            'last_action' => $last_action,
            'data' => $data,
            'urlvalemail' => $urlvalemail,
            'typeUser' => 'gateway',
        );
    }

    public function responsabilidadFiscal($idCliente, &$data)
    {
        $data["responsableIva"] = 0;
        if ($data['clasificacionRegimen'] === 'RC') {
            $data["responsableIva"] = 1;
        }

        foreach ($data["arrResponsabilidadesFiscales"] as $responsabilidadFiscal) {
            // Si el comercio es PJ,RC y ninguna responsabilidad se le agrega el codigo RESPONSABLE DE IVA(O-48) a las responsabilidades fiscales
            if (
                $data['clasificacionDian'] === 'PJ'
                && $data['clasificacionRegimen'] === 'RC'
                && $responsabilidadFiscal["id"] === FiscalResponsibilityCodes::NOT_RESPONSIBLE
            ) {
                $nuevaResponsabilidadFiscal = new ResponsabilidadFiscalClientes();
                $nuevaResponsabilidadFiscal->id_cliente = $idCliente;
                $nuevaResponsabilidadFiscal->id_responsabilidad_fiscal = FiscalResponsibilityCodes::IVA_RESPONSIBLE;
                $nuevaResponsabilidadFiscal->save();
            }

            //si tiene responsabilidad fiscal Régimen Simple de Tributación no es responsable de IVA
            if ($responsabilidadFiscal["id"] === FiscalResponsibilityCodes::SIMPLIFIED) {
                $data["responsableIva"] = 0;
            }

            $arResponsabilidadFiscal = new ResponsabilidadFiscal();
            $id = $responsabilidadFiscal["id"];
            $arResponsabilidadFiscal = $arResponsabilidadFiscal->where("id", $id)->first();
            if ($arResponsabilidadFiscal) {
                $responsabilidadFiscalCliente = new ResponsabilidadFiscalClientes();
                $responsabilidadFiscalCliente->id_cliente = $idCliente;
                $responsabilidadFiscalCliente->id_responsabilidad_fiscal = $arResponsabilidadFiscal->id;
                $responsabilidadFiscalCliente->save();
            }
        }
    }

    public function reconocimientoPublico($cliente, $reconocimientoPublico, $rolExpPosicionublica, $cargoExpPosicionublica, $fechaInicioCargoPublico, $fechaFinalCargoPublico, $ejerciendoCargoPublico)
    {
        $arClientesReconocimientoPublico = ClientesReconocimientoPublico::where("id_cliente", $cliente->Id)->first();
        if (!$arClientesReconocimientoPublico) $arClientesReconocimientoPublico = new ClientesReconocimientoPublico();
        $arClientesReconocimientoPublico->reconocimiento_publico = ($reconocimientoPublico);
        $arClientesReconocimientoPublico->rol = ($rolExpPosicionublica);
        $arClientesReconocimientoPublico->cargo = $cargoExpPosicionublica;
        if ($fechaInicioCargoPublico !== '') {
            $arClientesReconocimientoPublico->fecha_vinculacion = (new \DateTime($fechaInicioCargoPublico));
        }
        if (false === $ejerciendoCargoPublico) {
            $arClientesReconocimientoPublico->fecha_finalizacion = (new \DateTime($fechaFinalCargoPublico));
        }

        $arClientesReconocimientoPublico->ejerciendo = ($ejerciendoCargoPublico);
        $arClientesReconocimientoPublico->id_cliente = ($cliente->Id);
        $arClientesReconocimientoPublico->save();
    }

    public function getIndicativoRegion($depto)
    {

        $depto = Departamentos::find($depto);
        return "" . $depto->indicativo . "-";
    }

    public function documentoExiste($documento, $tipo)
    {

        return false;
        /*$em     = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RestBundle:Clientes')
                     ->findOneBy(array('documento'=>$documento,'tipoCliente'=>$tipo));

       if (is_object($entity)) {

                return false;
           }

         else {

                return false;
        }
        */

    }

    private function ValidarCedula($p_document, $p_fechaexpedicion)
    {

        return true;

        try {

            $wsdl = 'http://soap.epayco.co/soapclientes/ws/inspector';
            $sclient = new \SoapClient($wsdl);
            $p_idcustomer = '4877';
            $p_key = '0f76fdd58e1a94531c226a30e6a81197282897b3';
            $p_test = 'false';

            $result = $sclient->ValidarCedula($p_idcustomer, $p_key, $p_document, $p_fechaexpedicion, $p_test);

            if ($result->result == 1) {
                return true;
            } else {
                return false;
            }
        } catch (SoapFault $fault) {
            return false;
        }
    }


    public function emailExiste($email, $idEntityAllied)
    {
        $grantUser = GrantUser::where('email', $email)
            ->where('id_cliente_entidad_aliada', $idEntityAllied)
            ->first();

        if (is_object($grantUser)) {
            return true;
        } else {
            return false;
        }

    }

    public function generarkeyCli(&$entity)
    {
        $str = $this->randomString(10);
        $shaup = sha1($str);

        $entity->key_cli = ($shaup);

        return $shaup;

    }

    function randomString($length = 6)
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

    public function generarQr($id)
    {

        //parametros para generar el cÃ³digo QR
        $host = $this->request->server->get("HTTP_HOST");
        $url = $this->request->server->get("PATH_INFO");
        $ruta = 'images/QRcodes/clientes/' . $id . '.png';
        $prefix = 'https://';
        // se genera el CÃ³digo QR en la ruta especificada
        \PHPQRCode\QRcode::png($id, $ruta, 'L', 4, 2);

        return $prefix . $host . $url . "/" . $ruta;
    }

    public function inspectorRegistro($cliente)
    {

        $iregistro = new InspectorRegistro();
        $iregistro->fecha = (new \DateTime('now'));
        $iregistro->validemail = (0);
        $iregistro->validcelular = (0);
        $iregistro->logcifincedula = (0);
        $iregistro->lastconfronta = (0);
        $iregistro->lastevaluacionconfronta = (0);
        $iregistro->estadoconfronta = (0);
        $iregistro->cliente_id = ($cliente->Id);
        $iregistro->lastupdate = (new \DateTime('now'));
        $iregistro->save();
    }

    private function InsertarDetalleCliente(
        $cliente,
        $clasificacionDian,
        $clasificacionRegimen,
        $idRegion = "",
        $idCiudad = ""
    )
    {

        $dc = new DetalleClientes();

        $comisionvisa = '2.99';
        $comisionmaster = '2.99';
        $comisionamerican = '2.99';
        $comisiondiners = '2.99';
        $comisionpse = '2.99';
        $comisionPresencial = '2.99';
        $comisiontransaccionPresencial = '600';
        $comisiontransaccion = '600';
        $comisiontransaccion_pse = '600';
        $comisionretiro = '6500';

        $dc->id_cliente = ($cliente->Id);
        $dc->comisionvisa = ($comisionvisa);
        $dc->comisionmaster = ($comisionmaster);
        $dc->comisionamerican = ($comisionamerican);
        $dc->comisiondiners = ($comisiondiners);
        $dc->comisiontransaccion = ($comisiontransaccion);
        $dc->comision_presencial = ($comisionPresencial);
        $dc->comision_transaccion_presencial = ($comisiontransaccionPresencial);
        $dc->comision_pse = ($comisionpse);
        $dc->comision_transaccion_pse = ($comisiontransaccion_pse);
        $dc->comision_retiro = ($comisionretiro);

        $dc->saldo_cliente = ('0');
        $dc->tipocuenta = ('1');
        $dc->banco = (0);
        $dc->titular = ("");
        $dc->ncuenta = ('0000000000');
        $dc->clasificacion_dian = ($clasificacionDian);
        $dc->clasificacion_regimen = ($clasificacionRegimen);

        if ($idRegion) {
            $dc->ica_region = ($idRegion);
            $dc->ica_ciudad = ($idCiudad);
        }

        $dc->save();

    }

    public function insertarFiltros($clienteId, $tipocliente)
    {

        $filtrosdefault = WsFiltrosDefault::all();

        foreach ($filtrosdefault as $filtro) {

            //de filtros default a filtros de clientes
            $filtroscli = new WsFiltrosClientes();

            $filtroscli->id_cliente = ($clienteId);
            $filtroscli->filtro = ($filtro->filtro);
            $filtroscli->estado = ($filtro->estado);

            $filtromonto = json_decode($this->filtrosMontos($filtro->filtro, $tipocliente));

            if ($filtromonto->success) {
                if ($filtro->filtro == 1) {
                    $filtroscli->valor = 5000000;
                } else if ($filtro->filtro == 3) {
                    $filtroscli->valor = ($filtro->valor);
                } else {
                    $filtroscli->valor = ($filtromonto->valor);
                }

            } else {

                $filtroscli->valor = ($filtro->valor);

            }

            if ($filtro->filtro == 26 || $filtro->filtro == 27 || $filtro->filtro == 28 || $filtro->filtro == 29) {
                $filtroscli->estado = 0;
            }

            if ($filtro->filtro == 24 || $filtro->filtro == 36) {
                $filtroscli->valor = 80;
            }

            $filtroscli->save();

        }

        $template = WsPlantilla::where("id_tipo_plan", self::GATEWAY)->first();

        $newWsConfigClient = new WsConfiguracionCliente();
        $newWsConfigClient->id_cliente = $clienteId;
        $newWsConfigClient->id_configuracion_regla = $template->id_configuracion_regla;
        $newWsConfigClient->activo = self::ACTIVE;
        $newWsConfigClient->save();

    }

    public function filtrosMontos($fitroId, $tipoCliente)
    {


        $filtrosmonto = WSFiltrosMontos::where('tipo_cliente', $tipoCliente)
            ->where('filtro_id', $fitroId)->first();

        if ($filtrosmonto) {

            return json_encode(array('success' => true,
                'filtro' => $filtrosmonto->filtro_id,
                'valor' => $filtrosmonto->valor
            ));

        } else {

            return json_encode(array('success' => false));

        }

    }

    public function insertarMediosPagoGateway($clienteId)
    {

        //medios de pago disponibles
        $mediospago = MediosPago::all();

        foreach ($mediospago as $medio) {

            //medios de pagos activados para el cliente
            $filtroscli = new MediosPagoClientes();

            $filtroscli->id_cliente = ($clienteId);
            $filtroscli->id_medio = ($medio->Id);
            $filtroscli->estado = (0);
            $filtroscli->bancaria_id = (0);
            $filtroscli->red = ($medio->Id == "PSE") ? 5 : 0;
            $filtroscli->save();

        }

    }

    public function insertarValidacionCuenta($clienteId, $perfilId, $num_validaciones)
    {


        for ($index = 1; $index < $num_validaciones; $index++) {

            //consultar ls validaciones del cliente
            $limperfiles = LimPerfilesValidacion::where('validacion_id', $index)
                ->where('perfil_id', $perfilId)
                ->first();

            $limcliente = new LimClientesValidacion();

            $limcliente->cliente_id = ($clienteId);
            $limcliente->validacion_id = ($index);
            $limcliente->porcentaje = ($limperfiles->porcentaje);
            $limcliente->estado_id = (4);

            $limcliente->save();

        }

        //Omitir validacion de contrato, esto se hace ingresando el tipo doc 3
        //y dandole respuesta de omitir validacion
        //$tipo_doc=$em->getRepository('RestBundle:TipoDocumentosLegales','t')->find(3);

        $documentos_legales = new DocumentosLegales();
        $documentos_legales->cliente_id = ($clienteId);
        $documentos_legales->tipo_doc = (3);
        $documentos_legales->fecha_creacion = (new \DateTime('now'));
        $documentos_legales->respuesta_id = (12);
        $documentos_legales->subido = ("NO");
        $documentos_legales->aprobado = ("SI");
        $documentos_legales->save();

    }

    public function editarLimites($clienteId, $validacionId, $estadoId)
    {

        $limites = LimClientesValidacion::where('cliente_id', $clienteId)
            ->where('validacion_id', $validacionId)
            ->first();

        if ($limites) {

            $limites->estado_id = ($estadoId);
            $limites->save();

            return true;

        } else {

            return false;
        }
    }

    private function insertConfiguracion($cliente, $emailOption3, bool $isGateway, $clientCountry)
    {
        
        
        /**
         * @var $cliente Clientes
         */
        $telefono = "";
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

        
        $strtelefono = json_encode(array('cod_pais' => $cod_pais, 'ind_pais' => $ind_pais, 'ind_ciudad' => "", 'telefono' => $telefono));

        $valores = array(
            "9" => $strtelefono,
            "2" => $email,
            "3" => $emailOption3,
            "4" => $email,
            "14" => $email,
            "15" => $clientCountry->cod_moneda,
            "16" => "ES",
            "17" => "0",
            "54"=>$clientCountry->id,
            "57"=>$clientCountry->confMonedaId
        );

        if ($isGateway) {
            $valores['27'] = '1';
            $valores['28'] = '1';
            $valores['29'] = '1';
        }

        foreach ($valores as $key => $value) {
            $detconf = new DetalleConfClientes();
            $detconf->cliente_id = ($cliente->Id);
            $detconf->config_id = ($key);
            $detconf->valor = ($value);
            $detconf->save();

        }
        //Insertamos las llaves publicas

        $baseUrl = env("BASE_URL_REST");
        $baseUrlEntorno = env("BASE_URL_REST_ENTORNO");
        $url = "{$baseUrl}/{$baseUrlEntorno}/key/cliente.json?id=" . $cliente->Id;

        $this->sendCurlVariables($url, [], "GET");

//        $data = json_decode(file_get_contents($url));

        /*
          //Insertar llaves
          $email=$cliente->getEmail();
          $password=$this->getRequest()->get('password');
          $idcliente=$cliente->getId();

          $url_user="https://dashboard.epayco.io/clientes/user/newuser/rest?email=".$email.'&password='.$password."&clienteid=".$idcliente;
          $data=file_get_contents($url_user);
          */

    }

    private function InsertarCuentaBancaria($cliente, $tipo_cuenta, $numero_tarjeta, $id_banco)
    {

        try {
            //se encripta el número de la cuenta bancaria
            $number_encript = $this->encriptar($numero_tarjeta);
            //se valida que el número de tarjeta no exista y no pertenezcaa otro usuario
            $existecard = CuentasBancarias::where('cliente_id', $cliente->Id)
                ->where('numero_tarjeta', $number_encript)
                ->first();

            if (!$existecard) {

                $cbancaria = new CuentasBancarias();

                $tipoC = TiposCuenta::where("codigo", $tipo_cuenta)->first();

                $cbancaria->cliente_id = ($cliente->Id);
                $cbancaria->tipo_cuenta_id = ($tipoC->id);
                $cbancaria->banco_id = ($id_banco);
                $cbancaria->numero_tarjeta = ($number_encript);
                $cbancaria->numero_corto = (substr($numero_tarjeta, -4));
                $cbancaria->respuesta_id = (0);
                $cbancaria->estado_id = (2);

                $cbancaria->save();

                //actualizar medio pago
                $this->editarLimites($cliente->Id, 5, 2);

                $mask = 'xxxxxx' . $cbancaria->numero_corto;

                $banco = Bancos::find($id_banco);

                //consultar respuesta
                $respBancaria = CuentasBancariasRespuestas::find(0);


                $bankrta = $respBancaria->respuesta;


                $arrban = array('id_tarjeta' => $cbancaria->id,
                    'numero' => $mask,
                    'banco' => $banco->nombre,
                    'tipo_cuenta' => $tipoC->tipo,
                    'estado' => 'Pendiente',
                    'respuesta' => $bankrta,

                );

                /**
                 * @var $cliente Clientes
                 */
                $nombre_cliente = $cliente->nombre . " " . $cliente->apellido;

                if ($cliente->tipo_cliente == 'C') {
                    $nombre_cliente = $cliente->razon_social;
                    $nit = $cliente->documento;
                } else {
                    $nombre_cliente = $cliente->nombre . " " . $cliente->apellido;
                    $nit = $cliente->documento;
                }
                //enviar correo de nueva cuenta creada
//                $this->emailEnviar('Cuenta Bancaria '.$mask.' Creada en ePayCo',
//                    'no-responder@payco.co',
//                    array($cliente->email,'transacciones@payco.co'),
//                    'RestBundle:email:nueva_cuenta_bancaria.html.twig',
//                    array('bancaria' => $arrban,
//                        'cliente'  => $nombre_cliente,
//                        'email'    => $cliente->email,
//                        'nit'      => $nit
//
//                    )
//                );

                return true;

            } else {

                return false;

            }

        } catch (DBALException $ex) {

            return false;
        }


    }

    public function encriptar($text)
    {

        $descryptObj = new DescryptObject();
        $imputKey = getenv('INPUT_KEY');
        $text_encrypt = $descryptObj->setTextEncript($text, $imputKey);

        return $text_encrypt;
    }

    public function generateString($length = 5)
    {
        $string = "";
        $regex = "0123456789";
        $i = 0;
        while ($i < $length) {
            $char = substr($regex, mt_rand(0, strlen($regex) - 1), 1);
            $string .= $char;
            $i++;
        }
        return $string;
    }

    public function PagoAfiliacionGatewayAction($idCliente)
    {
        $conf_afiliacion_IVA = ConfAfiliaciones::find(1);
        $conf_afiliacion = ConfAfiliaciones::find(2);

        $cliente = Clientes::find($idCliente);
        $detalleCliente = DetalleClientes::where('id_cliente', $idCliente)->first();

        if ($cliente) {
            $porcentajeIva = TaxCodes::IVA;
            $mesproximo = date('m') + 1;
            $fecha_limite = date("Y-m-d", strtotime("-7 days", strtotime(date("Y-$mesproximo-d"))));
            $subtotal = $conf_afiliacion->valor;
            $iva = round($subtotal * 0.19, 2);
            $desc = 0;
            $valordesc = 0;
            $fechaLim = $fecha_limite;
            $idestado = 1; //NO PAGADA
            $concepto = "Pago Afiliacion Modelo Gateway " . $cliente->nombre_empresa;
            $item = "Pago Afiliacion Modelo Gateway " . $cliente->nombre_empresa;
            $tipo = 5; //Afiliacion modelo gateway
            $cantidad = 1;
            $consecutivo = "";
            $codigoint = time();

            $existeFactura = FacturasProforma::where("id_cliente", $cliente->Id)
                ->where("fecha", new \DateTime())
                ->where("id_estado", 1)
                ->where('tipo', $tipo)
                ->first();

            if ($existeFactura) {
                $factura = $existeFactura;
            } else {
                $factura = new FacturasProforma();
                $factura->fecha = new \DateTime();
                $factura->id_cliente = $cliente->Id;
                $factura->concepto = $concepto;
                $factura->consecutivo = $consecutivo;

                $factura->tipo = $tipo;
                $factura->total = $subtotal;
                $factura->subtotal = $subtotal;
                $factura->iva = $iva;
                $factura->porc_iva = $porcentajeIva;
                $factura->descuento = $desc;
                $factura->valor_descuento = $valordesc;
                //$factura->retencion_enlafuente = ($retencionEnlafuente);
                $factura->codinterno = $codigoint;
                $factura->fecha_limite = new \DateTime($fechaLim);
                $factura->id_estado = $idestado;
                $factura->retencion_enlafuente = 0;
                $factura->retencion_iva = 0;
                $factura->retencion_ica = 0;

                // nuevos campos de facturacion electronica
                $responsabilidadesFiscales = new ResponsabilidadFiscalClientes();
                $responsabilidadesClientes = $responsabilidadesFiscales->where(['id_cliente' => $idCliente])->get()->toArray();

                if (null === $responsabilidadesClientes || empty($responsabilidadesClientes)) {
                    //para clientes que registra davivienda que no tienen responsabilidades fiscales
                    $factura->retencion_enlafuente = (round($subtotal * TaxCodes::RETE_FUENTE_35 / 100, 2));
                    $factura->porc_retencion_enlafuente = (TaxCodes::RETE_FUENTE_35);
                }

                foreach ($responsabilidadesClientes as $responsabilidad) {
                    if (FiscalResponsibilityCodes::IVA_RESPONSIBLE === $responsabilidad['id_responsabilidad_fiscal']) {
                        //responsable de iva se le aplica retencion en la fuente y continua a la siguiente responsabilidad
                        $factura->retencion_enlafuente = (round($subtotal * TaxCodes::RETE_FUENTE_35 / 100, 2));
                        $factura->porc_retencion_enlafuente = (TaxCodes::RETE_FUENTE_35);
                        continue;
                    }
                    if (FiscalResponsibilityCodes::BIG_CONTRIBUTOR === $responsabilidad['id_responsabilidad_fiscal']
                        || FiscalResponsibilityCodes::SELFRETAINER === $responsabilidad['id_responsabilidad_fiscal']
                        || FiscalResponsibilityCodes::RET_IVA_RESPONSIBLE === $responsabilidad['id_responsabilidad_fiscal']
                    ) {
                        //gran contribuyente o autorretenedor se le aplica rete fuente, rete Iva y rete ica(si aplica)
                        $factura->retencion_iva = (round($iva * TaxCodes::RETE_IVA / 100, 2));
                        $factura->porc_retencion_iva = (TaxCodes::RETE_IVA);

                        //validar si la factura ya tiene retefuente, si no aplicarsela
                        if (0 === $factura->retencion_enlafuente || null === $factura->retencion_enlafuente) {
                            $factura->retencion_enlafuente = (round($subtotal * TaxCodes::RETE_FUENTE_35 / 100, 2));
                            $factura->porc_retencion_enlafuente = (TaxCodes::RETE_FUENTE_35);
                        }
                        //validar si aplica reteIca(solo para medellin)
                        if (70 === $detalleCliente->ica_ciudad
                            && FiscalResponsibilityCodes::RET_IVA_RESPONSIBLE !== $responsabilidad['id_responsabilidad_fiscal']
                        ) {
                            $factura->porc_retencion_ica = (TaxCodes::RETE_ICA);
                            $factura->retencion_ica = (round($subtotal * TaxCodes::RETE_ICA / 100, 2));
                        }
                        break;
                    }
                }

                $factura->total_neto = $subtotal + $iva - $factura->retencion_enlafuente - $factura->retencion_ica - $factura->retencion_iva;
                $factura->save();

                $factura->consecutivo = ($factura->id);
                $factura->save();
            }

            //Guardamos el detalle
            if (!$existeFactura) {
                $dfacturaProforma = new DetalleFacturasProforma();
                $dfacturaProforma->id_factura = ($factura->id);
                $dfacturaProforma->cantidad = ($cantidad);
                $dfacturaProforma->item = ($item);
                $dfacturaProforma->valor_unitario = ($subtotal);
                $dfacturaProforma->subtotal = ($subtotal);
                $dfacturaProforma->producto_id = (94);
                $dfacturaProforma->save();
            }

            $dfactura = DetalleFacturasProforma::where("id_factura", $factura->id)->first();
//            }

            //Buscar la ciudad del cliente
//            $ubicacion=$this->getUbicacion($cliente);
//            $ciudad=$ubicacion['ciudad'];

//            if($request->get("cupon")!=""){
            //Buscar los cupones
//                $strcupon=strtoupper($request->get("cupon"));
//                $cupon=$em->getRepository("PagosBundle:CuponesDescuento",'cp')->findOneByCodigo($strcupon);
            $now = strtotime(date("Y-m-d H:i:s"));

            $url_invoice_landing = getenv('URL_INVOICE_LANDING');
            $url_proforma_token = sha1($factura->id . 'ForSecurityYouMustUpdateThisToken');
            $url_proforma = $url_invoice_landing . '/proforma/' . $factura->id . '/' . $url_proforma_token;

            $json = array(
                "success" => true,
                "title_response" => "proforma creada",
                "text_response" => "proforma creada con éxito",
                "data" => [
                    "proforma" => $factura->id,
                    "urlProforma" => $url_proforma,
                ]
            );
            return $json;

//            }

//            $data=array('entity_cliente'=>$cliente,'detalle'=>$dfactura,'entity'=>$factura,'ciudad'=>$ciudad);

//            if($esproforma){
//
//                return $this->render('PagosBundle:Factura:factura_proforma.html.twig',$data);
//
//            }else{
//                return $this->render('PagosBundle:Factura:factura.html.twig',$data);
//
//            }

        } else {
            throw $this->createNotFoundException("Link roto");
        }
    }

    public function sendCurlVariables($url, $variables, $tipo_envio, $json = false, $headers =[])
    {

        $postvars = '';
        $par_clientes = array();

        foreach ($variables as $key => $value) {
            $postvars .= $key . "=" . $value . "&";
        }
        if ($tipo_envio == 'GET') {
            $par_clientes = explode('?', $url);
            $arr_vars_cliente = array();
            if (count($par_clientes) >= 2) {
                if (count($par_clientes) > 2) {
                    for ($i = 2; $i < count($par_clientes); $i++) {
                        $par_clientes[1] .= "?" . $par_clientes[$i];
                    }
                }
                $query = explode('&', $par_clientes[1]);

                foreach ($query as $key => $value) {
                    $vars = explode('=', $value);
                    $varkey = $vars[0];
                    $valor = $vars[1];
                    $arr_vars_cliente[$varkey] = $valor;
                }
                $var_adicionales = '?' . http_build_query($arr_vars_cliente) . '&';
            } else {
                $var_adicionales = "?";
                $par_clientes[0] = $url;
            }
            $url = $par_clientes[0] . $var_adicionales . http_build_query($variables);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        if ($tipo_envio == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (!$json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }//0 for a get request
        }

//        if($this->autenticacion==true){
//
//            curl_setopt($ch,CURLOPT_HTTPHEADER ,array(
//                'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password)));
//        }



        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000); //timeout in seconds

        if ($json) {
            $varEncode = json_encode($variables);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $varEncode);
            if (count($headers) > 0) {
                curl_setopt($ch,CURLOPT_HTTPHEADER , $headers);
            }else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($varEncode))
                );
            }

        }

        $response = curl_exec($ch);

        curl_close($ch);
        $arrRequests = explode("\r\n\r\n", $response);

        $body = end($arrRequests);

        $header_code = '500';
        $header = $this->get_headers_from_curl_response($response);
        if ($header) {
            $code_header = ($header[0]['http_code']);
            $exp_header = explode(" ", $code_header);

            if (is_array($exp_header)) {
                if (isset($exp_header[1])) {
                    $header_code = $exp_header[1];
                }
            }
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        } else {
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        }


    }

    private function get_headers_from_curl_response($headerContent)
    {

        $headers = array();

        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);
        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) - 1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }


    /**
     * @param $entity Clientes
     * @return array
     */
    private function getUbicacion($entity)
    {


        $idpais = $entity->id_pais;
        if ($idpais != "") {
            $objpais = Paises::where("codigo_pais", $idpais)->first();

            $pais = $objpais->nombre_pais;
            $pais_id = $objpais->id;

        } else {
            $objpais = false;
        }

        $idciudad = $entity->id_ciudad;
        if ($idciudad != "") {
            if ($idpais == 'CO') {
                $objciudad = Municipios::find($idciudad);
                if ($objciudad) {
                    $objdepto = Departamentos::find($objciudad->id_departamento);
                } else {
                    $objciudad = false;
                    $objdepto = false;
                }

            } else {

                if ($idciudad != "") {
                    $objciudad = Cities::find($idciudad);

                    if ($entity->id_region != "") {
                        $objdepto = Regions::find($entity->id_region);
                    }

                } else {

                }
            }

        } else {
            $objciudad = false;
            $objdepto = false;
        }

        if ($objciudad) {
            $ciudad = $objciudad->nombre;
            $ciudad_id = isset($objciudad->id) ? $objciudad->id : $objciudad->ID;
        } else {
            $ciudad = "";
            $ciudad_id = "";
        }

        if ($objdepto) {
            $deptoname = $objdepto->nombre;
            $departamento_id = isset($objdepto->id) ? $objdepto->id : $objdepto->ID;
        } else {
            $deptoname = "";
            $departamento_id = "";
        }


        return array('pais' => $pais, 'ciudad' => $ciudad, 'depto' => $deptoname);

    }

    private function setClientPersonOrCommerce(PreRegister $preRegister, $password)
    {
        $client = new Clientes();
        
        $requestData = json_decode($preRegister->request, true);
        try {
            $tipoDocumento = TipoDocumentos::where('codigo', $preRegister->doc_type)->first();
            if ($tipoDocumento) {
                $client->tipo_doc = $tipoDocumento->id;
            }

            $isGateway = $preRegister->plan_id === TiposPlanId::GATEWAY;

            $client->fecha_creacion = new \DateTime('now');
            $client->id_plan = $preRegister->plan_id? $preRegister->plan_id: TiposPlanId::AGREGADOR;
            $client->fase_integracion = 1;
            $client->id_estado = 1;
            $client->detalle_estado = 1;
            $client->metatag_registro_id = $preRegister->meta_tag == "ecommerceDay" ? 6 : 1;
            $client->documento = $preRegister->doc_number;
            $client->celular = $preRegister->cel_number;
            $client->id_pais = $preRegister->country;
            $client->ind_pais = $this->countryIndicative($preRegister->country);
            $client->id_categoria = isset($preRegister->category) ? $preRegister->category : null;
            $client->id_subcategoria = isset($preRegister->subcategory) ? $preRegister->subcategory : null;
            $client->slug = isset((json_decode($preRegister->request, true))['slug'])
                ? (json_decode($preRegister->request, true))['slug']
                : null;
            $client->restricted_user = $preRegister->restricted_user ?? null;

            $filterEmail = filter_var($preRegister->email, FILTER_SANITIZE_EMAIL);
            $email = strtolower($filterEmail);
            $client->email = $email;
            $client->contrasena = $this->encriptar($client->documento);
            if ($preRegister->user_type === 'persona') {
                $client->nombre = $preRegister->names;
                $client->apellido = $preRegister->surnames;
                $client->nombre_empresa = $preRegister->names . ' ' . $preRegister->surnames;
                $client->tipo_cliente = 'P';
                $client->perfil_id = 77;
                $client->tipo_usuario = 1;
            } else {
                $client->nombre_empresa = $preRegister->nombre_empresa;
                $client->razon_social = $preRegister->nombre_empresa;
                $client->digito = $preRegister->digito;
                $client->tipo_cliente = 'C';
                $client->perfil_id = 99;
                $client->tipo_usuario = 2;
            }

            $client->save();
            $client->key_cli = $this->generarkeyCli($client);

            $salesForceService = new SalesForceService();
            $getTokenSalesforce =  $salesForceService->getTokenSalesForce();


            $informationToken = json_decode($getTokenSalesforce["body"]);
            $token = $informationToken->access_token;

            $salesForceService->setLeadSalesForce($token, $preRegister);

            //se inserta el log en inspector registro (agregador & gateway)
            $this->inspectorRegistro($client);

            //insertar validacion de cuenta (agregador & gateway)
            $this->insertarValidacionCuenta($client->Id, $client->perfil_id, 6);

            if ($isGateway) {
                // insertar detalles cliente (gateway)
                $this->InsertarDetalleCliente($client, "", "", "", "");

                // insertar filtros al cliente (gateway)
                $this->insertarFiltros($client->Id, 'C');

                // insertar medios de pago (gateway)
                $this->insertarMediosPagoGateway($client->Id);

                // validamos la ubicación del negocio paso 3
                $this->editarLimites($client->Id, 3, 1);

                // se inserta tickect y modal de ticke al cliente
                $this->generateTicket($client);
                // se inserta modal de afiliación gateway al cliente
                $modalClientesService = new ModalClientesService();
                $modalClientesService->createModalCliente($client->Id, ModalConfigId::AFILIACION_GATEWAY);
                // se inserta modal de productos escalonados al cliente
                $modalClientesService->createModalCliente($client->Id, ModalConfigId::PRODUCT_STAIR);
                // se inserta pago por consumo al cliente
                $clientProductService = new ClientProductService();
                $clientProductService->createActiveClientProduct($client->Id, ProductosId::PAGO_POR_CONSUMO);
            } else {
                //insertar filtros
                $this->aggregatorInsertFilters($client->Id, $client->tipo_cliente === 'C' ? 'C' : 'P');

                //insertar medios de pago
                $this->aggregatorPaymentMethods($client->Id);

                //instertar detalle del cliente
                $this->addAggregatorPlan($client->Id, 'estandar');
            }
            
            //si es persona se valida el paso
            if ($client->tipo_cliente === 'P') {
                $this->editarLimites($client->Id, 4, 1);
            }

            //insertar configuracion del cliente
            $currencyCode = CommonValidation::validateIsSet($requestData,"multiAccountCurrencyCode",CommonText::COP_CURRENCY_CODE);
            $clientCountryData = $this->getClientCountryData($client->id_pais,$currencyCode,$requestData["isMultiAccount"]);
            $this->insertConfiguracion($client, $client->email, $isGateway, $clientCountryData);
            $client->save();

            return $client;
        } catch (\Exception $exception) {
            $client->delete();
            return false;
        }
    }

    private function getClientCountryData($clientCountryId,$currencyCode=CommonText::COP_CURRENCY_CODE,$isMultiAccount=false){

        $confCountryExist = ConfPais::where("cod_pais",$clientCountryId)->first();

        $countryCode = is_null($confCountryExist)?CommonText::COUNTRY_CODE_CO:$clientCountryId;

        $query = ConfPais::select("conf_pais.id", "conf_moneda.cod_moneda","conf_moneda.id as confMonedaId")
            ->join("pais_moneda", "pais_moneda.conf_pais_id", "=", "conf_pais.id")
            ->join("conf_moneda", "conf_moneda.id", "=", "pais_moneda.conf_moneda_id")
            ->where("conf_pais.cod_pais", $countryCode);

        if($isMultiAccount){
            $query->where("conf_moneda.cod_moneda", $currencyCode);
        }else{
            $query->where("pais_moneda.principal", 1);
        }

        return $query->first();
    }

    private function countryIndicative($countryCode)
    {
        $indicativeExists = Paises::where('codigo_pais', $countryCode)->get()->first();
        $indicative = "";
        if ($indicativeExists) {
            $indicative = $indicativeExists->indicativo;
        }

        return $indicative;
    }

    private function aggregatorPaymentMethods($clientId)
    {
        $paymentMethods = MediosPago::all();

        foreach ($paymentMethods as $paymentMethod) {
            $filtroscli = new MediosPagoClientes();
            $filtroscli->id_cliente = $clientId;
            $filtroscli->id_medio = $paymentMethod->Id;
            $filtroscli->comision = $paymentMethod->comision_cliente;
            $filtroscli->valor_comision = $paymentMethod->valor_comision_cliente;
            $filtroscli->estado = $paymentMethod->activo;
            $filtroscli->save();
        }

        $tariffPse = MediosPagoTarifafija::find('PSE');

        $tariffClientPse = new MediosPagoTarifafijaClientes();
        $tariffClientPse->cliente_id = $clientId;
        $tariffClientPse->medio_pago_id = 'PSE';
        $tariffClientPse->valormaximo = $tariffPse->valormaximo;
        $tariffClientPse->valor_comision_cliente = $tariffPse->valor_comision_cliente;
        $tariffClientPse->estado = 1;
        $tariffClientPse->save();
    }

    private function addAggregatorPlan($clientId, $plan)
    {
        $planId = $plan === 'estandar' ? 1 : 2;

        $configPlan = ConfigPlanFijo::find($planId);

        //se configura el plan y queda en estado pendiente
        $clientPlan = new PlanFijoClientes();
        $clientPlan->cliente_id = ($clientId);
        $clientPlan->comision_franquicias = $configPlan->comision_franquicias;
        $clientPlan->comision_tr_credito = $configPlan->comision_tr_credito;
        $clientPlan->comision_tr_pse = $configPlan->comision_franquicias;
        $clientPlan->comision_tr_presencial = $configPlan->comision_tr_presencial;
        $clientPlan->comision_retiro = $configPlan->comision_retiro;
        $clientPlan->valor_mensual = $configPlan->valor_mensual;
        $clientPlan->estado = (2);
        $clientPlan->save();

        //se agrega la solicitud del plan en estado pendiente por activar
        $planNuevo = new PlanesClientes();
        $planNuevo->fecha_solicitud = date("Y-m-d H:i:s");
        $planNuevo->cliente_id = ($clientId);
        $planNuevo->plan_id = ($clientPlan->id);
        $planNuevo->estado = (2);
        $planNuevo->save();

        $this->insertAggregatorCommissions(
            $clientId,
            $configPlan->comision_franquicias,
            $configPlan->comision_tr_credito,
            $configPlan->comision_tr_presencial,
            $configPlan->comision_retiro
        );
    }

    private function insertAggregatorCommissions(
        $clientId,
        $franchiseCommission,
        $comcredito,
        $compresencial,
        $comretiro
    )
    {
        $dc = new DetalleClientes();

        $dc->id_cliente = ($clientId);
        $dc->comisionvisa = $franchiseCommission;
        $dc->comisionmaster = $franchiseCommission;
        $dc->comisionamerican = $franchiseCommission;
        $dc->comisiondiners = $franchiseCommission;
        $dc->comision_pse = $franchiseCommission;
        $dc->comisiontransaccion = $comcredito;
        $dc->comision_presencial = $franchiseCommission;
        $dc->comision_transaccion_presencial = $compresencial;
        $dc->comision_transaccion_pse = $comcredito;
        $dc->comision_retiro = $comretiro;

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

    private function aggregatorInsertFilters($clientId, $clientType)
    {
        $defaultFilters = WsFiltrosDefault::all();

        foreach ($defaultFilters as $filter) {
            $clientFilter = new WsFiltrosClientes();
            $clientFilter->id_cliente = ($clientId);
            $clientFilter->filtro = ($filter->filtro);
            $clientFilter->estado = ($filter->estado);

            $valueFilter = json_decode($this->filtrosMontos($filter->filtro, $clientType));

            if ($valueFilter->success) {
                if ($filter->filtro == 1) {
                    $clientFilter->valor = $filter->valor;
                } else {
                    $clientFilter->valor = $valueFilter->valor;
                }

            } else {
                $clientFilter->valor = $filter->valor;
            }
            $clientFilter->save();
        }

        if ($clientType == self::PERSON) {
            $template = WsPlantilla::where("tipo_cliente", self::PERSON)
                ->where("id_tipo_plan", self::AGGREGATOR)->first();
        }

        if ($clientType == self::COMMERCE) {
            $template = WsPlantilla::where("tipo_cliente", self::COMMERCE)
                ->where("id_tipo_plan", self::AGGREGATOR)->first();
        }

        $newWsConfigClient = new WsConfiguracionCliente();
        $newWsConfigClient->id_cliente = $clientId;
        $newWsConfigClient->id_configuracion_regla = $template->id_configuracion_regla;
        $newWsConfigClient->activo = self::ACTIVE;
        $newWsConfigClient->save();
    }

    public function addUserAccount($clientId, $grantUserId, $accountName , $duplicateClientId) {

            try{
                $userAccount = new UserCuenta();
                $userAccount->grant_user_id = $grantUserId;
                $userAccount->cliente_id = $clientId;
                $userAccount->estado = 1;
                $userAccount->nombre_cuenta = $accountName;
                $userAccount->cliente_id_duplicado = $duplicateClientId;
                $userAccount->save();
            }
            catch (Exception $ex){
                throw new GeneralException($ex->getMessage());
            }
    }

    private function generateTicket($client)
    {
        $dataCreateTicket  = [
            "clientId" => $client->Id,
            "pregunta" => "Vinculación de cliente Gateway bajo creación de comercio {$client->nombre_empresa} y ID {$client->Id}",
            "asunto" => "Solicitud de soporte",
            "departamento" => TckDepartamentosId::COMERCIAL,
            "prioridad" => TckPrioridadId::BAJA,
            "files" => null,
            "sendMailToClient" => false,
            "success" => true,
        ];

        $processCreateTicket = event(
            new ProcessCreateTicketEvent($dataCreateTicket),
        );

        if ($processCreateTicket[0]["success"]) {
            $modalClientService = new ModalClientesService();
            $modalClientService->createModalCliente($client->Id, ModalConfigId::TICKET_GATEWAY);
        }
    }
}
