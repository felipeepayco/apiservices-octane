<?php
namespace App\Listeners\Catalogue\Validation;


use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueCategoriesListListener extends HelperPago
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
    public function handle(ValidationGeneralCatalogueCategoriesListEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }


        if(isset($data["filter"])){
            if(is_array($data["filter"])){
                $filter=(object)$data["filter"];
            }else if(is_object($data["filter"])){
                $filter=$data["filter"];
            }else{
                $validate->setError(500,"field filter is type object");
            }
        }else{
            $filter=[];
        }

        $arr_respuesta["filter"]=$filter;

        $pagination = [];
        if (isset($data["pagination"])) {
            if (is_array($data["pagination"])) {
                $pagination = (object)$data["pagination"];
            } else if (is_object($data["pagination"])) {
                $pagination = $data["pagination"];
            } else {
                $validate->setError(500, "field pagination is type object");
            }
        }

        $arr_respuesta["pagination"] = $pagination;

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

        if( $validate->totalerrors > 0 ){
            $success        = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array('totalerrors'=>$validate->totalerrors,
                      'errors'     =>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse'   => $title_response,
                'textResponse'    => $text_response,
                'lastAction'      => $last_action,
                'data'            => $data
            );
            //dd($response);
            $this->saveLog(2,$clientId, '', $response,'consult_catalogue_categories_list');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}