<?php
namespace App\Listeners;


use App\Events\ConsultSellListEvent;
use App\Events\ConsultSellNewEvent;
use App\Events\ConsultSellUpdateEvent;
use App\Events\ValidationGeneralSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Cobros;
use App\Models\CompartirCobro;
use App\Models\FilesCobro;
use App\Models\Trm;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use Illuminate\Http\Request;
use App\Helpers\Messages\CommonText;

class ConsultSellUpdateListener extends HelperPago {

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
    public function handle(ConsultSellUpdateEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $reference=$fieldValidation["reference"];
            $amount=$fieldValidation["amount"];
            $currency=$fieldValidation["currency"];
            $id=$fieldValidation["id"];
            $base=isset($fieldValidation["base"])?$fieldValidation["base"]:null;
            $description=$fieldValidation["description"];
            $title=$fieldValidation["title"];
            $sellType=$fieldValidation["typeSell"];
            $urlConfirmation=$fieldValidation["urlConfirmation"];
            $urlResponse=$fieldValidation["urlResponse"];
            $tax=isset($fieldValidation["tax"])&&$fieldValidation["tax"]!==""?$fieldValidation["tax"]:0;
            $expirationDate=$fieldValidation["expirationDate"];
//            $img=$fieldValidation["img"];
//            $document=$fieldValidation["document"];
            $email=isset($fieldValidation["email"])?$fieldValidation["email"]:"";
            $cellPhone=isset($fieldValidation["mobilePhone"])?$fieldValidation["mobilePhone"]:"";
            $indicativo=isset($fieldValidation["indicative"])?$fieldValidation["indicative"]:"";


            $filtroMontoMax = WsFiltrosClientes::Where("id_cliente","=",$clientId)
                ->where("filtro","1")->first();

            if ($reference == "") {
                $reference = \uniqid($clientId);
            }


            if ($filtroMontoMax) {
                $cantmaxcobro = $filtroMontoMax->valor;
            } else {
                $filtroMontoMaxDefault = WsFiltrosDefault::where("filtro",1)->first();
                $cantmaxcobro = $filtroMontoMaxDefault->valor;
            }

            if ($currency == 'COP') {
                $valor_ok = $amount;
            } else {
                $objtrm = Trm::where("Id",1);
                $trm = $objtrm->trm_actual;
                $valor_ok = $amount * $trm;
            }
            $total_ok = $valor_ok;
            if ($total_ok > $cantmaxcobro) {
                $success = false;
                $title_response = 'Error';
                $text_response = "Maximum amount exceeded, the maximum amount allowed for your account is $" . number_format($cantmaxcobro) . " COP";
                $last_action = 'create new sell';
                $data=[];
                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            //Fin validación maximo
            /** @var  $cobro Cobros */
            $cobro = new Cobros();
            $cobro = Cobros::where("id",$id)->where("estado",">=",1)->first();
            $esnuevo = false;
            $cobro->cliente_id=$clientId;
            $cobro->descripcion=$description;
            $cobro->titulo=$title;
            $cobro->moneda=$currency;
            $cobro->tipocobro=$sellType;
            $cobro->url_confirmacion=$urlConfirmation;
            $cobro->url_respuesta=$urlResponse;

            $cobro->iva=$tax;
            $cobro->valor=$amount;
            $cobro->numerofactura=$reference;
            $cobro->estado=1;
            $cobro->fecha_expiracion=$expirationDate;
            $cobro->rutaqr="";
            $cobro->save();



//                if ($img) {
//                    if(is_object($img)){
//                        $newImg[0]=$img;
//                        $img=$newImg;
//                    }
//                    $totalImg = count($img);
//                    if ($totalImg > 0) {
//                        for ($k = 0; $k < $totalImg; $k++) {
//                            $fechaActual = new \DateTime('now');
//                            $name = $img[$k]->getClientOriginalName();
//                            $tmp_name = $img[$k]->getPathname();
//                            $fileTipe = $img[$k]->getClientOriginalExtension();
//
//                            //Subir los archivos
//                            $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$fileTipe}";
//                            $urlFile = "cobros/files/{$nameFile}";
//
//                            $filecobro = new FilesCobro();
//                            $filecobro->fechacreacion=$fechaActual;
//                            $filecobro->nombre=$name;
//                            $filecobro->tipo=1;
//                            $filecobro->url=$urlFile;
//                            $filecobro->cobro_id=$cobro->id;
//                            $filecobro->save();
//
//                            //Subir a rackespace
//                            $this->uploadDocumentosLegales($nameFile, $tmp_name);
//                        }
//                    }
//                }
//                $doc = $document;
//                if ($doc) {
//                    $fechaActual = new \DateTime('now');
//                    $name = $doc->getClientOriginalName();
//                    $tmp_name = $doc->getPathname();
//                    $fileTipe = $doc->getClientOriginalExtension();
//                    if ($tmp_name != "") {
//                        //Subir los archivos
//                        $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$fileTipe}";
//                        $urlFile = "cobros/files/{$nameFile}";
//
//                        $filecobro = new Filescobro();
//                        $filecobro->fechacreacion=$fechaActual;
//                        $filecobro->nombre=$name;
//                        $filecobro->tipo=2;
//                        $filecobro->url=$urlFile;
//                        $filecobro->cobro_id=$cobro->id;
//                        $filecobro->save();
//
//                        //Subir a rackespace
//                        $this->uploadDocumentosLegales($nameFile, $tmp_name);
//                    }
//                }
            $txtcodigo = str_pad($cobro->Id, '5', "0", STR_PAD_LEFT);

            $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
            $url2 = '/apprest';

            $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';


            $urltxtcodigo="https://payco.link/{$cobro->Id}";
            $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;

            $code = $this->sendCurlVariables($url_qr, [], "GET", true);

            $cobro->txtcodigo=$txtcodigo;
            $cobro->rutaqr=$rutaqr;
            $cobro->save();

            $mensaje = "";
            if ($sellType == '1') {

                $compartircobro = new CompartirCobro();
                $compartircobro->fecha=new \DateTime('now');
                $compartircobro->mensaje="";
                $compartircobro->tipoenvio=2;
                $compartircobro->valor=$email;
                $compartircobro->cobro_id=$cobro->id;
                $compartircobro->save();
                $mensaje = "Email de cobro creado correctamente";
                $this->enviaremailcobro($cobro, $compartircobro);
            }
            if ($sellType == '3') {

                $compartircobro = new Compartircobro();
                $compartircobro->fecha=new \DateTime('now');
                $compartircobro->mensaje="";
                $compartircobro->tipoenvio=3;
                $compartircobro->valor=$cellPhone;
                $compartircobro->cobro_id=$cobro->id;
                $compartircobro->save();
                $mensaje = $cellPhone;

                $this->enviarsmscobro($cobro, $indicativo, $compartircobro);
            }

            $newData=[
                "date"=>$cobro->fecha->format("Y-m-d H:i:s"),
                "state"=>$cobro->estado,
                "txtCode"=>$cobro->txtcodigo,
                "clientId"=>$cobro->cliente_id,
                "onePayment"=>$cobro->cobrounico,
                "quantity"=>$cobro->cantidad,
                "baseTax"=>$cobro->base_iva,
                "description"=>$cobro->descripcion,
                "title"=>$cobro->titulo,
                "currency"=>$cobro->moneda,
                "typeSell"=>$cobro->tipocobro,
                "urlConfirmation"=>$cobro->url_confirmacion,
                "urlResponse"=>$cobro->url_respuesta,
                "tax"=>$cobro->iva,
                "amount"=>$cobro->valor,
                "invoceNumber"=>$cobro->numerofactura,
                "expirationDate"=>$cobro->fecha_expiracion,
                "routeQr"=>$url_qr,
                "routeLink"=>$urltxtcodigo,
                "id"=>$cobro->id
            ];
            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;

        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al actualizar el cobro con los parametros datos";
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