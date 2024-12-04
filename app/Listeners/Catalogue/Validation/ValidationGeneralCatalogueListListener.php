<?php
namespace App\Listeners\Catalogue\Validation;


use App\Events\Catalogue\Validation\ValidationGeneralCatalogueListEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueProductListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationGeneralCatalogueListListener extends HelperPago {

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
    public function handle(ValidationGeneralCatalogueListEvent $event)
    {
        $validate=new Validate();

        $data=$event->arr_parametros;

        $clientId = $this->validateIsSet($data,'clientId',false,'int');
        $this->validateParamFormat($clientId,'int',$validate,'filter',true);
        $filter = $this->validateIsSet($data,'filter',false,'object');
        $this->validateParamFormat($filter,'object',$validate,'filter',false);
        $pagination = $this->validateIsSet($data,'pagination',false,'object');
        $this->validateParamFormat($pagination,'object',$validate,'pagination',false);

        $arr_respuesta["clientId"] = $clientId;
        $arr_respuesta["filter"] = $filter;
        $arr_respuesta["pagination"] = $pagination;

        if( $validate->totalerrors > 0 ){
            $success        = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array('totalerrors'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'       => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'          => $data
            );
            $this->saveLog(2,$clientId, '', $response,'consult_catalogue_list');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }

    private function validateIsSet($data,$key,$default,$cast=""){



        $content = $default;

        if (isset($data[$key])) {
            if($cast=="int"){
                $content = (int) $data[$key];
            }else if($cast=="string"){
                $content = (string) $data[$key];
            }else if($cast=="object"){
                $content = (object) $data[$key];
            }else{
                $content = $data[$key];
            }
        }

        return $content;

    }

    private function validateParamFormat($validated,$validateType,&$validate,$paramName,$required=true){

        if($required && !isset($validated) ){
            $validate->setError(500, 'field '.$paramName.' required');
        }

        if(isset($validated) && $paramName === 'filter') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }

        if(isset($validated) && $paramName === 'clientId') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }
    }
}