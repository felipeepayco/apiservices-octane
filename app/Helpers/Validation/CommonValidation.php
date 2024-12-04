<?php

namespace App\Helpers\Validation;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;

use App\Repositories\V2\ClientRepository;
use App\Helpers\Messages\CommonText as CM;
use App\Common\PlanSubscriptionStateCodes;

class CommonValidation
{

    public static function getFieldValidation($fields, $name, $default = "")
    {
        return isset($fields[$name]) ? $fields[$name] : $default;
    }

    public static function validateIsSet($data, $key, $default, $cast = "", $noZero = false)
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
                case "float": {
                        $content = (float) $data[$key];
                        if ($noZero && $content == 0) {
                            $content = "";
                        }
                        break;
                    }
                case "date":
                    $content = date("Y-m-d H:i:s", strtotime($data[$key]));
                    break;
                case "bool":
                    $content = (bool) $data[$key];
                    break;
                default:
                    $content = $data[$key];
                    break;
            }
        }

        return $content;

    }

    public static function validateBase64Image($data)
    {

        try {
            if (filter_var($data, FILTER_VALIDATE_URL)) {
                return true;
            }

            $parts = explode(',', $data);
            if (count($parts) < 2) {

                return false;

            }
            $header = $parts[0];
            if (!preg_match('/^data:image\/.+?base64/', $header)) {
                return false;

            }

            $format = explode('/', substr($header, strlen('data:image/')));
            $format = str_replace(";base64", "", $format[0]);

            if (!in_array($format, ['png', 'jpg', 'jpeg'])) {

                return false;
            }

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }


    public static function validateParamFormat(&$arr_respuesta, $validate, $param, $paramName, $validateType, $required = true, $range = [0, 0])
    {
        if (isset($param)) {
            $vparam = true;
            switch ($validateType) {
                case "empty":
                    $vparam = $validate->ValidateVacio($param, $paramName);
                    break;
                case "phone":
                    $vparam = $validate->ValidatePhone($param);
                    break;
                case "email":
                    $vparam = $validate->ValidateEmail($param, $paramName);
                    break;
                case "range":
                    $vparam = $validate->ValidateStringSize($param, $range[0], $range[1]);
                    break;
                case "array":
                    $vparam = $validate->ValidateArray($param);
                    break;
                case "bool":
                    $vparam = $validate->validateBoolValue($param);
                    break;
                case "int":
                    $vparam = $validate->validateInt($param);
                    break;
            }

            if (!$vparam) {
                $validate->setError(500, 'field ' . $paramName . ' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if ($required) {
                $validate->setError(500, 'field ' . $paramName . ' required');
            }
        }
    }
    public function mayorZero($number)
    {
        if ($number > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function validateDateFormat(&$arr_respuesta, $validate, $param, $paramName, $required = true)
    {
        if (isset($param)) {
            $vparam = true;

            try {
                $param = new \DateTime($param);
            } catch (\Exception $exception) {
                $validate->setError(500, "field expirationDate invalidate date type");
            }

            if (!$vparam) {
                $validate->setError(500, CM::FIELD . ' ' . $paramName . ' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if ($required) {
                $validate->setError(500, CM::FIELD . ' ' . $paramName . ' required');
            }
        }
    }

    public static function getPhoneNumber($fieldValidation)
    {
        $phoneNumber = self::getFieldValidation($fieldValidation, "contactNumber", null);
        if ($phoneNumber && strlen($phoneNumber) == 10) {
            $phoneNumber = '+57' . $phoneNumber;
        }

        return $phoneNumber;
    }

    public static function validationId($fieldValidation): array
    {
        $data = (array) $fieldValidation;
        ///id unico ///
        $timeArray = explode(" ", microtime());
        $timeArray[0] = str_replace('.', '', $timeArray[0]);
        $txtcodigo = str_pad((int) ($timeArray[1] . $timeArray[0]), '5', "0", STR_PAD_LEFT);

        $idProd = (int) ($timeArray[1] . substr($timeArray[0], 2, 3));

        if (isset($data["id"]) && trim($data["id"] != "") && $data["id"] != null) {
            $idProd = $data["id"];
        }

        return array($idProd, $txtcodigo);
    }

    public static function ternaryHelper($codition, $value, $default)
    {
        try {
            return $codition ? $value : $default;
        } catch (\Exception $exception) {
            return $default;
        }
    }

    public static function validateActiveSubscription($bblClientId)
    {
       
        $bblClient = app(ClientRepository::class);
        $lastSubscription = $bblClient->subscriptionState($bblClientId)->estado;
        return (PlanSubscriptionStateCodes::ACTIVE == $lastSubscription) ? true:false;
    }

}