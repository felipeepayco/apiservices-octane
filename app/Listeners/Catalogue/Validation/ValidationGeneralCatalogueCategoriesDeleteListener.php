<?php
namespace App\Listeners\Catalogue\Validation;


use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesDeleteEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueCategoriesDeleteListener extends HelperPago {

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
    public function handle(ValidationGeneralCatalogueCategoriesDeleteEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;
        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }

        if(isset($data['id'])){
            $id = (integer)$data['id'];
        } else {
            $id = false ;
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

        if(isset($id)){
            $vid = $validate->ValidateVacio($id, 'id');
            if (!$vid) {
                $validate->setError(500, "field id required");
            } else{
                $arr_respuesta['id'] = $id;
            }
        }else{
            $validate->setError(500, "field id required");
        }

        if( $validate->totalerrors > 0 ){
            $success        = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array('totalerrors'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse'   => $title_response,
                'textResponse'    => $text_response,
                'lastAction'      => $last_action,
                'data'            => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response,'consult_delete_sell');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}