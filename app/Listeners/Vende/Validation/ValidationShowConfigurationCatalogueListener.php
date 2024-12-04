<?php
namespace App\Listeners\Vende\Validation;


use App\Events\Vende\Validation\ValidationShowConfigurationCatalogueEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText;
use Illuminate\Http\Request;

class ValidationShowConfigurationCatalogueListener extends HelperPago {

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
    public function handle(ValidationShowConfigurationCatalogueEvent $event)
    {
        $validate=new Validate();
        $data=$event->arr_parametros;
        $clientId = CommonValidation::validateIsSet($data,'clientId',null,'int');
        $filter = CommonValidation::validateIsSet($data, 'filter', null, 'object');

        CommonValidation::validateParamFormat($arr_respuesta,$validate,$clientId,'clientId',CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$filter,'filter',CommonText::EMPTY, false);
        
        if($validate->totalerrors > 0 ){
            $success         = false;
            $last_action     = 'validation clientId y data of filter';
            $title_response  = 'Error';
            $text_response   = 'Some fields are required, please correct the errors and try again';

            $data            =
                array('totalerrors'=>$validate->totalerrors,
                    'errors'=>$validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse'   => $title_response,
                'textResponse'    => $text_response,
                'lastAction'      => $last_action,
                'data'            => $data
            );
            $this->saveLog(2, '', $response,'consult_configuration_catalogue',$clientId);
            return $response;
        }

        $arr_respuesta['success'] = true;
        return $arr_respuesta;

    }
}