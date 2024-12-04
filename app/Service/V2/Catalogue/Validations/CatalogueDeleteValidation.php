<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class CatalogueDeleteValidation
{

    public $response;

    public function validate(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();

        if (isset($data['clientId'])) {
            $clientId = (int) $data['clientId'];
        } else {
            $clientId = false;
        }

    
        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } 
        } else {
            $validate->setError(500, "field clientId required");
        }

        if (isset($data['id'])) {
            $vid = $validate->ValidateVacio($data['id'], 'id');
            if (!$vid) {
                $validate->setError(500, "field id required");
            } 

            $this->validateNumericParameters($data,'id',20,$validate);

        } else {
            $validate->setError(500, "field id required");
        }


        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
                array(
                    'totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            $response = array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data
            );

            return $response;
        }

        $arr_respuesta['success'] = true;
        $arr_respuesta['data'] = $data;
        $arr_respuesta['titleResponse'] = "catalog is valid";
        $arr_respuesta['textResponse'] = "catalog is valid";
        $arr_respuesta['success'] = true;

       return $arr_respuesta;
    }
    private function validateParamFormat($validated, $validateType, &$validate, $paramName, $required = true)
    {

        if ($required && !isset($validated)) {
            $validate->setError(500, 'field ' . $paramName . ' required');
        }

        if (isset($validated) && $paramName === 'filter') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }

        if (isset($validated) && $paramName === 'clientId') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }
    }
    public static function validateIsSet($data, $key, $default, $cast = "")
    {

        $content = $default;

        if (isset($data[$key])) {
            switch ($cast) {
                case "int":
                    $content = (int) $data[$key];
                    break;
                case "string":
                    $content = (string) $data[$key];
                    break;
                case "date":
                    $content = date("Y-m-d H:i:s", strtotime($data[$key]));
                    break;
                default:
                    $content = $data[$key];
                    break;
            }
        }

        return $content;
    }

    private function validateNumericParameters($data, $parameter_name, $length, $validate, $allow_zero = false)
    {
        if (isset($data[$parameter_name])) {
            $zero = ($allow_zero) ? $data[$parameter_name] != "0" : $data[$parameter_name] != "";

            if ($zero) {
                if (!$validate->validateIsNumeric($data[$parameter_name])) {

                    $validate->setError(422, "{$parameter_name} field is invalid, numeric value expected");
                } else {
                    $parameter_length = floor(log10(abs($data[$parameter_name]))) + 1;

                    if ($parameter_length > $length) {

                        $validate->setError(422, "$parameter_name field can not be greater than $length digits");

                    }

                    if ($data[$parameter_name] < 1) {

                        $validate->setError(422, "$parameter_name field must be greater than 0");

                    }
                }
            }

        }
    }
}
