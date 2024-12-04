<?php
namespace App\Listeners;


use App\Events\ValidationGeneralCatalogueProductReferenceCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueProductReferenceCreateListener extends HelperPago
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
    public function handle(ValidationGeneralCatalogueProductReferenceCreateEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

        //se valida el cliente
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



        //se valida el nombre de la referencia
        if(isset($data['name'])){
            $name = $data['name'];
        } else {

            $name = false ;
        }

        if(isset($name)){
            $vname = $validate->ValidateVacio($name, 'name');
            if (!$vname) {
                $validate->setError(500, "field name required");
            }
            elseif(strlen($name)<3) {
                $validate->setError(500, "field name min 3 characters");
            }
            elseif(strlen($name)>150) {
                $validate->setError(500, "field name max 150 characters");
            }
            else{
                $arr_respuesta['name'] = $name;
            }
        }else{
            $validate->setError(500, "field name required");
        }



        //se valida la cantidad stock de la referencia

        if(isset($data['quantity'])){
            $quantity = $data['quantity'];
        } else {

            $quantity = false ;
        }


        if(isset($quantity)){
            $vquantity = $validate->ValidateVacio($quantity, 'quantity');
            if (!$vquantity) {
                $validate->setError(500, "field quantity required");
            }
            elseif(is_numeric($quantity)){
                if(is_string($quantity))$quantity=(int)$quantity;
                if($quantity<=0) {
                    $validate->setError(500, "field quantity min 1");
                }
                else{
                    $arr_respuesta['quantity'] = $quantity;
                }
            }else{
                $validate->setError(500, "field quantity type numeric");
            }

        }else{
            $validate->setError(500, "field quantity required");
        }



        //se valida la el valor de la referencia

        if(isset($data['amount'])){
            $amount = $data['amount'];
        } else {

            $amount = false ;
        }


        if(isset($amount)){
            $vamount = $validate->ValidateVacio($amount, 'amount');
            if (!$vamount) {
                $validate->setError(500, "field amount required");
            }
            elseif(is_numeric($amount)){
                if(is_string($amount))$amount=(float)$amount;
                if($amount<=0) {
                    $validate->setError(500, "field amount min 1");
                }
                else{
                    $arr_respuesta['amount'] = $amount;
                }
            }else{
                $validate->setError(500, "field amount type numeric");
            }

        }else{
            $validate->setError(500, "field amount required");
        }


        if(isset($data['catalogue'])){
            $catalogue = (integer)$data['catalogue'];
        } else {
            $catalogue = false ;
        }

        if(isset($catalogue)){
            $vcatalogue = $validate->ValidateVacio($catalogue, 'catalogue');
            if (!$vcatalogue) {
                $validate->setError(500, "field catalogue required");
            } else{
                $arr_respuesta['catalogue'] = $catalogue;
            }
        }else{
            $validate->setError(500, "field catalogue required");
        }

        if(isset($data['img'])){
            $img = $data['img'];
            $arr_respuesta['img'] = $img;
        } else {
            $img = null ;
            $arr_respuesta['img'] = $img;
        }




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
            $this->saveLog(2,$clientId, '', $response,'consult_catalogue_product_reference_create');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}