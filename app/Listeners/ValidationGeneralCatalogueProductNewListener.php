<?php
namespace App\Listeners;


use App\Events\ValidationGeneralCatalogueProductNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Catalogo;

class ValidationGeneralCatalogueProductNewListener extends HelperPago
{

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
    public function handle(ValidationGeneralCatalogueProductNewEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;

        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }

        if(isset($data['catalogueId'])){
            $catalogueId = (integer)$data['catalogueId'];
        } else {
            $catalogueId = false ;
        }

        if(isset($catalogueId)){
            $vcatalogueId = $validate->ValidateVacio($catalogueId, 'catalogueId');
            if (!$vcatalogueId) {
                $validate->setError(500, "field catalogueId required");
            } else{
                $arr_respuesta['catalogueId'] = $catalogueId;
            }
        }else{
            $validate->setError(500, "field catalogueId required");
        }

        if(isset($data['id'])){
            $idCobro = (integer)$data['id'];
        } else {
            $idCobro = false ;
        }

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

        $tipocobro=1;

        if(isset($data['quantity'])){
            $cantidad=$data['quantity'];
        }else{
            $cantidad="";
        }

        $disponibles = $cantidad;
        if ($idCobro > 0) {
            $disponibles = $disponibles != "" ? (int)$disponibles : $cantidad;
        }

        $arr_respuesta["available"]=$disponibles;

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

        if(isset($data['baseTax'])){
            $base=$data['baseTax'];
            $arr_respuesta["baseTax"]=$base;
        }else{
            $base=0;
            $arr_respuesta["baseTax"]=$base;
        }

        if ($iva != 0) {
            $iva = $base * ($iva / 100);
            //$base = $valor - $iva;
            $arr_respuesta["baseTax"]=$base;
            $arr_respuesta["tax"]=$iva;
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

        $arr_respuesta['description'] = $descripcion;

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


        if(isset($data['shippingTypes'])){
            $shippingTypes=$data['shippingTypes'];
        }else{
            $shippingTypes=null;
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


        if(isset($data['img'])){
            $img=$data['img'];
        }else{
            $img=null;
        }
        $arr_respuesta["img"]=$img;


        if(isset($data['contactName'])){
            $contactName=$data['contactName'];
        }else{
            $contactName=null;
        }
        $arr_respuesta["contactName"]=$contactName;

        if(isset($data['contactNumber'])){
            $contactNumber=$data['contactNumber'];
        }else{
            $contactNumber=null;
        }
        $arr_respuesta["contactNumber"]=$contactNumber;

        if(isset($data['document'])){
            $documento=$data['document'];
        }else{
            $documento=null;
        }
        $arr_respuesta["document"]=$documento;


        if(isset($data['productReferences'])){
            $productReference=$data['productReferences'];
        }else{
            $productReference=null;
        }

        $arr_respuesta['productReferences'] = $productReference;

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

        if(isset($idCobro)){
            $vidCobro = $validate->ValidateVacio($idCobro, 'id');
            if (!$vidCobro) {
                $validate->setError(500, "field id required");
            } else{
                $arr_respuesta['id'] = $idCobro;
            }
        }else{
            $validate->setError(500, "field id required");
        }


        if(isset($shippingTypes)){
            $arr_respuesta['shippingTypes'] = $shippingTypes;

        }else{
            $validate->setError(500, "field shippingTypes required");
        }


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
            $vvalor = $validate->ValidateVacio($valor, 'amount');
            if (!$vvalor) {
                $validate->setError(500, "field amount required");
            } else{
                $arr_respuesta['amount'] = $valor;
            }
        }else{
            $validate->setError(500, "field amount required");
        }


        if(isset($cantidad)){
            $vcantidad = $validate->ValidateVacio($cantidad, 'quantity');
            if (!$vcantidad) {
                $validate->setError(500, "field quantity required");
            } else{
                $arr_respuesta['quantity'] = $cantidad;
            }
        }else{
            $validate->setError(500, "field quantity required");
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

//        if(isset($descripcion)){
//            $vdescripcion = $validate->ValidateVacio($descripcion, 'description');
//            if (!$vdescripcion) {
//                $validate->setError(500, "field description required");
//            } else{
//                $arr_respuesta['description'] = $descripcion;
//            }
//        }else{
//            $validate->setError(500, "field description required");
//        }

        if($catalogueId>0){
            $existeCatalogo=Catalogo::find($catalogueId);
            if(!$existeCatalogo){
                $validate->setError(500, "the catalogueId:$catalogueId not exist");
            }
        }




        if( $validate->totalerrors > 0 ){
            $success         = false;
            $last_action    = 'validation data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array('totalerrors'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'title_response' => $title_response,
                'text_response'  => $text_response,
                'last_action'    => $last_action,
                'data'           => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response,'catalogue_product_new');

            return $response;
        }

        //Nueva categoria
        if(isset($data["category_new"])){
            $arr_respuesta['category_new']=$data["category_new"];
        }
        if(isset($data["categories"])){
            $arr_respuesta['categories']=$data["categories"];
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}