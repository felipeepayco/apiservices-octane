<?php
namespace App\Listeners;


use App\Events\ValidationGeneralSellUpdateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Cobros;
use Illuminate\Http\Request;

class ValidationGeneralSellUpdateListener extends HelperPago {

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
    public function handle(ValidationGeneralSellUpdateEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;

        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }

        if(isset($clientId)){
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else{
                $arr_respuesta['clientId'] = $clientId;
            }
        }else{
            $validate->setError(500, "field clientId required");
        }

        if(isset($data['id'])){
            $id = (integer)$data['id'];
        } else {
            $id = false ;
        }

        if(isset($id)){
            $vidCobro = $validate->ValidateVacio($id, 'id');
            if (!$vidCobro) {
                $validate->setError(500, "field id required");
            } else{
                $arr_respuesta['id'] = $id;
            }
        }else{
            $validate->setError(500, "field id required");
            if( $validate->totalerrors > 0 ){
                $success         = false;
                $last_action    = 'validation clientId y data of filter';
                $title_response = 'Error';
                $text_response  = 'Some fields are required, please correct the errors and try again';

                $data           =
                    array('totalerrors'=>$validate->totalerrors,
                        'errors'=>$validate->errorMessage);
                $response=array(
                    'success'         => $success,
                    'titleResponse' => $title_response,
                    'textResponse'  => $text_response,
                    'lastAction'    => $last_action,
                    'data'           => $data
                );
                //dd($response);
                $this->saveLog(2,$clientId, '', $response,'consult_delete_sell');

                return $response;
            }
        }


        $tipocobro=Cobros::where("cliente_id",$clientId)
            ->where("estado",">=",1)
            ->where("id","{$id}")
            ->select("tipocobro")->first();

        if(!$tipocobro){
            $validate->setError(500, "Sell not found");
        }else{
            $tipocobro=(int)$tipocobro->tipocobro;
            $arr_respuesta["typeSell"]=$tipocobro;
            if(isset($data['currency'])){
                $moneda = $data['currency'];
            } else {
                $moneda = false ;
            }

            if(isset($data['amount'])){
                $valor = $data['amount'];
            } else {
                $valor = false ;
            }

            if(isset($data['reference'])){
                $referencia = $data['reference'];
            } else {
                $referencia = "" ;
            }
            $arr_respuesta["reference"]=$referencia;

            $cobrounico = 0;
            $arr_respuesta["onePayment"]=$cobrounico;

            if(isset($data['urlConfirmation'])){
                $urlConfirmacion=$data['urlConfirmation'];
            }else{
                $urlConfirmacion="";
            }
            $arr_respuesta["urlConfirmation"]=$urlConfirmacion;

            if(isset($data['urlResponse'])){
                $urlRespuesta=$data['urlResponse'];
            }else{
                $urlRespuesta="";
            }

            $arr_respuesta["urlResponse"]=$urlRespuesta;

            if(isset($data['tax'])){
                $iva=$data['tax'];
            }else{
                $iva=0;
            }

            if ($iva != 0) {
                $iva = $valor * ($iva / 100);
                $base = $valor - $iva;
                $arr_respuesta["base"]=$base;
            }


            if(isset($data['title'])){
                $titulo=$data['title'];
            }else{
                $titulo="";
            }

            if(isset($data['description'])){
                $descripcion=$data['description'];
            }else{
                $descripcion="";
            }

            if(isset($data['email'])){
                $email=$data['email'];
            }else{
                $email="";
            }

            if(isset($data['mobilePhone'])){
                $celular=$data['mobilePhone'];
            }else{
                $celular="";
            }

            if(isset($data['indicative'])){
                $indicativo=$data['indicative'];
            }else{
                $indicativo="";
            }

            if(isset($data["expirationDate"])){
                if($data["expirationDate"]!=""){
                    try{
                        $fechavencimiento=new \DateTime($data["expirationDate"]);
                    }catch (Exception $exception){
                        $validate->setError(500,"field expirationDate invalidate date type");
                    }
                }else{
                    $fechavencimiento=null;
                }
                $fechavencimiento=$data["expirationDate"]!=""?new \DateTime($data["expirationDate"]):null;
            }else{
                $fechavencimiento=null;
            }
            $arr_respuesta["expirationDate"]=$fechavencimiento;



            if(isset($moneda)){
                $vmoneda = $validate->ValidateVacio($moneda, 'currency');
                if (!$vmoneda) {
                    $validate->setError(500, "field currency required");
                } else{
                    $arr_respuesta['currency'] = $moneda;
                }
            }else{
                $validate->setError(500, "field currency required");
            }

            if(isset($valor)){
                $vvalor = $validate->ValidateVacio($moneda, 'amount');
                if (!$vvalor) {
                    $validate->setError(500, "field amount required");
                } else{
                    $arr_respuesta['amount'] = $valor;
                }
            }else{
                $validate->setError(500, "field amount required");
            }



            if(isset($titulo)){
                $vtitulo = $validate->ValidateVacio($titulo, 'title');
                if (!$vtitulo) {
                    $validate->setError(500, "field title required");
                } else{
                    $arr_respuesta['title'] = $titulo;
                }
            }else{
                $validate->setError(500, "field title required");
            }

            if(isset($descripcion)){
                $vdescripcion = $validate->ValidateVacio($descripcion, 'description');
                if (!$vdescripcion) {
                    $validate->setError(500, "field description required");
                } else{
                    $arr_respuesta['description'] = $descripcion;
                }
            }else{
                $validate->setError(500, "field description required");
            }

            if($tipocobro==1){
                if(isset($email)){
                    $vemail = $validate->ValidateVacio($email, 'email');
                    if (!$vemail) {
                        $validate->setError(500, "field email required");
                    } else{
                        $arr_respuesta['email'] = $email;
                    }
                }else{
                    $validate->setError(500, "field email required");
                }
            }else if($tipocobro==3){
                if(isset($celular)){
                    $vcelular = $validate->ValidateVacio($celular, 'mobilePhone');
                    if (!$vcelular) {
                        $validate->setError(500, "field mobilePhone required");
                    } else{
                        $arr_respuesta['mobilePhone'] = $celular;
                    }
                }else{
                    $validate->setError(500, "field mobilePhone required");
                }

                if(isset($indicativo)){
                    $vindicativo = $validate->ValidateVacio($indicativo, 'indicative');
                    if (!$vindicativo) {
                        $validate->setError(500, "field indicative required");
                    } else{
                        $arr_respuesta['indicative'] = $indicativo;
                    }
                }else{
                    $validate->setError(500, "field indicative required");
                }
            }
        }



        if( $validate->totalerrors > 0 ){
            $success         = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array('totalerros'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response,'consult_delete_sell');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}