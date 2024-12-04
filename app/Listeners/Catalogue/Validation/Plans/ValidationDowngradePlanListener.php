<?php


namespace App\Listeners\Catalogue\Validation\Plans;



use App\Events\Catalogue\Validation\Plans\ValidationDowngradePlanEvent;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationDowngradePlanListener extends HelperPago
{
    /**
     * ValidationDowngradePlanListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    public function handle(ValidationDowngradePlanEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

        $arr_respuesta = [];

        $clientId = CommonValidation::validateIsSet($data,'clientId',false,'int');
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$clientId,'clientId','empty');

        $productId = CommonValidation::validateIsSet($data,'productId',false,'int');
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$productId,'productId','empty');

        $clientIdentifier = CommonValidation::validateIsSet($data,'clientIdentifier',false,'int');
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$clientIdentifier,'clientIdentifier','empty');

        if ($validate->totalerrors > 0 || $clientId != 4877) {
            $success        = false;
            $last_action    = 'validation data save';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data = [];

            if($clientId != 4877){
                $text_response = 'unauthorized';
            }else{
                $data = [
                    'totalErrors' => $validate->totalerrors,
                    'errors'      => $validate->errorMessage,
                ];
            }

            $response = [
                'success'        => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data,
            ];

            $this->saveLog(2,$clientId, '', $response, 'catalogue_new');

            return $response;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;
    }

}
