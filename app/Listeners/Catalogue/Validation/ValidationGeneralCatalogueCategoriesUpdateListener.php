<?php
namespace App\Listeners\Catalogue\Validation;


use App\Listeners\Services\VendeConfigPlanService;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Edata\HelperEdata;
use App\Http\Validation\Validate as Validate;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesNewEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueCategoriesUpdateEvent;

class ValidationGeneralCatalogueCategoriesUpdateListener extends HelperPago
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
    public function handle(ValidationGeneralCatalogueCategoriesUpdateEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $arr_respuesta = [];

        $clientId = $this->validateIsSet($data,'clientId',false,'int');
        $catalogueId = $this->validateIsSet($data,'catalogueId',false);
        $id = $this->validateIsSet($data,'id',false);
        $name = $this->validateIsSet($data,'name',false);
        
        $origin = $this->validateIsSet($data,'origin',false);
        $arr_respuesta['origin'] = $origin;

        $logo = $this->validateIsSet($data,'logo',false);
        $arr_respuesta['logo'] = $logo;

        $active = $this->validateIsSet($data,'active',true);
        $arr_respuesta['active'] = $active;
        
        if(isset($name)){
            $vname = $validate->ValidateVacio($name, 'name');
            if (!$vname) {
                $validate->setError(500, "field name required");
            }
            elseif(strlen($name) < 1) {
                $validate->setError(500, "field name min 1 characters");
            }
            elseif(strlen($name)>50) {
                $validate->setError(500, "field name max 50 characters");
            }
            else
            {
                $arr_respuesta['name'] = $name;
            }
        }else{
            $validate->setError(500, "field name required");
        }

        $this->validateParamFormat($arr_respuesta,$validate,$catalogueId,'catalogueId','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$id,'id','empty');
        $this->validateParamFormat($arr_respuesta,$validate,$clientId,'clientId','empty');

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

            $this->saveLog(2,$clientId, '', $response,'consult_catalogue_categories_create');

            return $response;
        }

        /* Aplicar validaciones para las reglas */
        $edata = new HelperEdata($this->request, $clientId);
        $idobjet = isset($arr_respuesta["id"]) ? $arr_respuesta["id"] : null;
        $vendeConfigService = new VendeConfigPlanService();

        if(!$vendeConfigService->activeCategoryAfterEdataAllowed($arr_respuesta)){
            if (!$edata->validarCategoria($name, $idobjet)) {
                $last_action = 'category_updated';
                $title_response = 'Error updated category';
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

                return [
                    'success'       => false,
                    'titleResponse' => $title_response,
                    'textResponse'  => $text_response,
                    'lastAction'    => $last_action,
                    'data'          => $data,
                ];

            }
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

    private function validateParamFormat(&$arr_respuesta,$validate,$param,$paramName,$validateType,$required=true){
        if (isset($param)) {
            $vparam = true;

            if($validateType == 'empty'){
                $vparam = $validate->ValidateVacio($param, $paramName);
            }else if($validateType == 'phone' && $param != ""){
                $vparam = $validate->ValidatePhone($param);
            }else if($validateType == 'email' && $param != ""){
                $vparam = $validate->ValidateEmail($param,$paramName);
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