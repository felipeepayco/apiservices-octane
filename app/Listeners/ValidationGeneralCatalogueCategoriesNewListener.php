<?php
namespace App\Listeners;


use App\Events\ValidationGeneralCatalogueCategoriesListEvent;
use App\Events\ValidationGeneralCatalogueCategoriesNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueCategoriesNewListener extends HelperPago
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
    public function handle(ValidationGeneralCatalogueCategoriesNewEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }

        if(isset($data['catalogueId'])){
            $catalogueId = $data['catalogueId'];
        } else {
            $catalogueId = false ;
        }

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
            else
            {
                $arr_respuesta['name'] = $name;
            }


        }else{
            $validate->setError(500, "field name required");
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
            $this->saveLog(2,$clientId, '', $response,'consult_catalogue_categories_create');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}