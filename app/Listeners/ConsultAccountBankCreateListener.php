<?php
namespace App\Listeners;



use App\Events\ConsultAccountBankCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Models\Clientes;
use App\Models\CuentasBancarias;
use App\Models\DocumentosLegales;
use App\Models\LimClientesValidacion;
use App\Models\MediosPagoClientes;
use App\Models\PasarelaConfig;
use App\Models\ProductosClientes;
use Illuminate\Http\Request;

class ConsultAccountBankCreateListener extends HelperPago {

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
    public function handle(ConsultAccountBankCreateEvent $event)
    {
        $fieldValidation = $event->arr_parametros;
        $clienteId=$fieldValidation["clientId"];
        $bankCode=$fieldValidation["bankCode"];
        $accountType=$fieldValidation["accountType"];
        $numberAccount=$fieldValidation["numberAccount"];
        $doc=isset($fieldValidation["document"])?$fieldValidation["document"]:null;

        $data = (object)[
            "numeroIdentificacion"=>"",
            "codigoBanco"=>$bankCode,
            "accountType"=>$accountType,
            "numberAccount"=>$numberAccount
        ];

        if ($doc) {
            $data = (object)["numeroIdentificacion" => "",
                "codigoBanco" => "",
                "accountType" => "",
                "numberAccount" => ""];
            $data->codigoBanco = $bankCode;
            $data->accountType = $accountType;
            $data->numeroIdentificacion = "";
            $data->numberAccount = $numberAccount;
        } else {
            if (!is_numeric($data->numberAccount)) {
                $this->decryptProductNumber($data->numberAccount);
            }
        }

        $codigoBancoDavivienda = 1421;
        $codigoBanco = $data->codigoBanco == "DP" || $data->codigoBanco == "CA" ? $codigoBancoDavivienda : $data->codigoBanco;
        $numeroCorto = substr($data->numberAccount, -4);
        $tipoCuenta = $data->accountType == "DP" || $data->accountType == "CA" ? 1 : 2;
        $fechaActual = new \DateTime("now");
        if ($data->accountType == "DP" || $data->codigoBanco == "DP") {
            $tipoCuenta = 3;
        }

        /**
         * @var $arClient Clientes
         */

        if ($data->numeroIdentificacion === "") {
            $arClient = Clientes::find($clienteId);
            $data->numeroIdentificacion = $arClient->documento;
        } else {
            $arClient = Clientes::where("documento" ,$data->numeroIdentificacion)->first();
        }
        if ($arClient) {
            /**
             * @var $arLimClienteValidacion LimClientesValidacion
             */
            $arLimClienteValidacion = LimClientesValidacion::where("cliente_id" ,$arClient->Id)->where("validacion_id",5)->first();
            $arCuentasBancarias = CuentasBancarias::where("cliente_id" ,$arClient->Id)->where("numero_corto",$numeroCorto)->where("banco_id",$codigoBanco)->where("tipo_cuenta_id",$tipoCuenta)->first();
            if (!$arCuentasBancarias) {
                $totalAccount = CuentasBancarias::where("cliente_id",$clienteId)->count();

                $arCuentasBancarias = new CuentasBancarias();
                //Encirptar numero de producto
                /**
                 * @var $configParam PasarelaConfig
                 */
                $configParam = PasarelaConfig::where("parametro","llaves_general")->first();
                $llaveGeneral = $configParam->valor;
                $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $llaveGeneral, $data->numberAccount, MCRYPT_MODE_ECB));

                $arCuentasBancarias->numero_tarjeta=$encrypted;
                $arCuentasBancarias->numero_corto=$numeroCorto;
                $arCuentasBancarias->banco_id=$codigoBanco;
                $arCuentasBancarias->cliente_id=$arClient->Id;
                $arCuentasBancarias->tipo_cuenta_id=$tipoCuenta;
                $arCuentasBancarias->estado_id=$data->codigoBanco == "DP" || $data->codigoBanco == "CA" ? 1 : 2;
                $arCuentasBancarias->respuesta_id=$data->codigoBanco == "DP" || $data->codigoBanco == "CA" ? 1 : 0;
                $arCuentasBancarias->fecha_apertura=$fechaActual;
                if ($totalAccount === 0) {
                    $arCuentasBancarias->predeterminada=1;
                    if (($data->codigoBanco == "DP" || $data->codigoBanco == "CA")) {
                        $this->setTarifasDavivienda($arClient);
                    }

                }
                if ($data->codigoBanco == "CA" || $data->codigoBanco == "DP") {
                    $arCuentasBancarias->tipo_cuenta_davivienda=$data->codigoBanco;
                }
                $arCuentasBancarias->save();

                if ($doc) {
                    $dataDocument = explode(',', $doc);
                    $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                    $name=explode("tmp/",$tmpfname);
                    $name=$name[1];
                    $sacarExt=explode('image/',$dataDocument[0]);
                    $sacarExt=isset($sacarExt[1])?explode(';',$sacarExt[1]):["pdf"];


                    $base64=base64_decode(isset($sacarExt[1])?$dataDocument[1]:$dataDocument[0]);
                    file_put_contents(
                        $tmpfname.".".$sacarExt[0],
                        $base64
                    );



                    $fechaActual = new \DateTime('now');

                    //Subir los archivos
                    $nameFile = "{$clienteId}_cuentabancaria_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                    $this->uploadDocumentosLegales($nameFile, $tmpfname);
                    //Guardar en documentos legales
                    $this->setCuentaBancaria($arClient,4, $name, $fechaActual, $sacarExt[0], $nameFile, $arCuentasBancarias->id);
                }
                //Buscar lim_clientes_validacion
                $arLimClienteValidacion->estado_id=$data->codigoBanco == "DP" || $data->codigoBanco == "CA" ? 1 : 2;
                $arLimClienteValidacion->save();
            }
        } else {
            $success = false;
            $title_response = 'client no found';
            $text_response = 'client no found';
            $last_action = 'client no found';
            $data = (object)$data;
            $arr_respuesta['success'] = $success;
            $arr_respuesta['titleResponse'] = $title_response;
            $arr_respuesta['textResponse'] = $text_response;
            $arr_respuesta['lastAction'] = $last_action;
            $arr_respuesta['data'] = $data;

            return $arr_respuesta;
        }

        $success = true;
        $title_response = 'account bank create';
        $text_response = 'account bank create';
        $last_action = 'account bank create';
        $data = (object)$data;
        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;

    }


    ////////////////Funciones para el crear el registro y la consulta del cron ///////////////////////////////////////////////////////
    private function setCuentaBancaria($cliente,$tipoDocumento, $nombre, $fechaActual, $extension, $url, $bancariaId)
    {

        $arDocumentosLegales = new DocumentosLegales();
        $arDocumentosLegales->cliente_id=$cliente->Id;
        $arDocumentosLegales->fecha_creacion=$fechaActual;
        $arDocumentosLegales->nombre=$nombre;
        $arDocumentosLegales->tipo_doc=$tipoDocumento;
        $arDocumentosLegales->extension=$extension;
        $arDocumentosLegales->url=$url;;
        $arDocumentosLegales->subido="SI";
        $arDocumentosLegales->respuesta_id=0;
        $arDocumentosLegales->bancaria_id=$bancariaId;
        $arDocumentosLegales->save();
    }



    private function setTarifasDavivienda($cliente)
    {

        $tarifa_davivienda = array("tarifa" => 2.68, 'valor' => 900);

        /**
         * @var $medios_pago_cliente MediosPagoClientes
         */
        $medios_pago_cliente = MediosPagoClientes::where("cliente_id",$cliente->Id)->first();
        foreach ($medios_pago_cliente as $medio) {
            /**
             * @var $medio MediosPagoClientes
             */
            $medio->comision=$tarifa_davivienda["tarifa"];
            $medio->valor_comision=$tarifa_davivienda["valor"];
            $medio->save();
        }
        //Ingresamos el productos clientes el pland de davivienda
        $productoCliente = new ProductosClientes();
        $productoCliente->fecha_creacion=new \DateTime("now");
        $productoCliente->fecha_inicio=new \DateTime("now");
        $productoCliente->estado=1;
        $productoCliente->cliente_id=$cliente->Id;
        $productoCliente->producto_id=74;
        $productoCliente->save();

    }
}