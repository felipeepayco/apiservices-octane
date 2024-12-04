<?php
namespace App\Listeners\Buttons\Validation;


use App\Events\Buttons\Validation\ValidationGeneralButtonDeleteEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralButtonDeleteListener extends HelperPago {

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ValidationGeneralButtonDeleteEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;

        $clientId = $validate->validateIsSet($data,'clientId',false);
        $id = $validate->validateIsSet($data, 'id',null);

        if (!$clientId) {
            $validate->setError(500, "field clientId required");
        } else{
            $arr_respuesta['clientId'] = $clientId;
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
            $this->saveLog(2,$clientId, '', $response,'consult_delete_sell');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}