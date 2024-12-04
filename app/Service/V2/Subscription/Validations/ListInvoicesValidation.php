<?php
namespace App\Service\V2\Subscription\Validations;

use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;
class listInvoicesValidation{
    public array $response;

    public function validate(Request $request)
    {
        $validate = new Validate();
        $arr_respuesta = $request->request->all();
        $clientId = CommonValidation::validateIsSet($arr_respuesta, 'clientId', false, 'int');
        CommonValidation::validateParamFormat($arr_respuesta,$validate,$clientId,'clientId','int');

        $arr_respuesta['pagination']= CommonValidation::getFieldValidation((array)$arr_respuesta, 'pagination', []);
        $arr_respuesta['page']= CommonValidation::getFieldValidation((array)$arr_respuesta['pagination'], 'page', 1);
        $arr_respuesta['limit'] = CommonValidation::getFieldValidation((array)$arr_respuesta['pagination'], 'limit', 50);

        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data = [
                    'totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                ];
            $response = [
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data
            ];

            $this->response = $response;
            return false;
        }
        $this->response = $arr_respuesta;
        return true;
    }

}