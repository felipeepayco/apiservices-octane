<?php
namespace App\Listeners\Catalogue\Validation;


use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Edata\HelperEdata;
use App\Http\Validation\Validate as Validate;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesNewEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesListEvent;

class ValidationGeneralCatalogueCategoriesNewListener extends HelperPago
{

    const EMPTY = 'empty';

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

        $arr_respuesta = [];

        $clientId = $this->validateIsSet($data,'clientId',false,'int');
        $catalogueId = $this->validateIsSet($data,'catalogueId',false,'int');
        $name = $this->validateIsSet($data,'name',false,'string');
        $logo = $this->validateIsSet($data,'logo',false);
        $arr_respuesta["origin"] = isset($data["origin"]) ? $data["origin"] : null;
        if(isset($data["origin"]) && $data["origin"]==='epayco'){
            $arr_respuesta['logo'] = $logo;
        }else{
            $arr_respuesta['logo'] = null;
        }

        $this->validateParamFormat($arr_respuesta,$validate,$clientId,'clientId',self::EMPTY);
        $this->validateParamFormat($arr_respuesta,$validate,$catalogueId,'catalogueId',self::EMPTY);
        $this->validateParamFormat($arr_respuesta,$validate,$name,'name','range',true,[1,50]);
        $this->validateParamFormat($arr_respuesta,$validate,$name,'name',self::EMPTY,true);

        

        

        if ($validate->totalerrors > 0 ) {
            $success         = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data = array('totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response=array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );

            $this->saveLog(2,$clientId, '', $response, 'consult_catalogue_categories_create');

            return $response;
        }


        /* Aplicar validaciones para las reglas */
        $edata = new HelperEdata($this->request, $clientId);
        $idobjet = isset($arr_respuesta["id"]) ? $arr_respuesta["id"] : null;
        if (!$edata->validarCategoria($name, $idobjet)) {
            $last_action = 'category_created';
            $title_response = 'Error created category';
            $text_response = $edata->getMensaje();

            $data = [
                'totalErrors' => 1,
                'errors' => [
                    [
                        'codError'     => 'AED100',
                        'errorMessage' => $text_response,
                    ]
                ]
            ];
            $response = [
                'success'       => false,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'          => $data,
            ];
            return $response;
        }

        $arr_respuesta['success']       = true;
        $arr_respuesta['id_edata']      = $edata->getIdEdata();
        $arr_respuesta['edata_estado']  = $edata->getEdataEstado();
        $arr_respuesta['edata_mensaje'] = $edata->getMensaje();
        return $arr_respuesta;
    }



    private function validateIsSet($data,$key,$default,$cast=""){

        $content = $default;
        
        if (isset($data[$key])) {
            if($cast=="int"){
                $content = (int) $data[$key];
            }else if($cast=="string"){
                $content = (string) $data[$key];
            }else{
                $content = $data[$key];
            }
        }

        return $content;

    }

    private function validateParamFormat(&$arr_respuesta,$validate,$param,$paramName,$validateType,$required=true,$range=[0,1]){
        if (isset($param)) {
            $vparam = true;

            if($validateType == self::EMPTY){
                $vparam = $validate->ValidateVacio($param, $paramName);
            }else if($validateType == 'string' && $param!=""){
                $vparam = $validate->ValidateStringSize($param,0,20);
            }else if($validateType == 'range'){
                $vparam = $validate->ValidateStringSize($param,$range[0],$range[1]);
            }
            
            if (!$vparam) {
                $validate->setError(500, 'field '.$paramName.' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if($required){
                $validate->setError(500, 'field '.$paramName.' required');
            }
        }
    }
}