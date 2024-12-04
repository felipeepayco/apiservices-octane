<?php

namespace App\Http\Validation;

use \DateTime;

class Validate
{
    public $errorMessage;
    public $totalerrors;
    public $validator;

    public function __construct()
    {
        $this->errorMessage = array();
        $this->totalerrors = 0;
    }

    public function ValidateEmail($email, $name)
    {
        if ($email != "") {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return true;
            } else {
                $error = $this->getErrorCheckout("A002");
                // esta *no* es una dirección de correo electrónico válida
                $this->errorMessage[] = array('codError' => $error->error_code, 'errorMessage' => trans("error.{$error->error_message}", ['field' => 'emailPayment', "fieldType" => "email"]));
                $this->totalerrors++;
                return false;
            }

        } else {
            $error = $this->getErrorCheckout("A001");
            $this->errorMessage[] = array('codError' => $error->error_code, 'errorMessage' => trans("error.{$error->error_message}", ['field' => 'emailPayment', "fieldType" => "email"]));
            $this->totalerrors++;
            return false;
        }
    }

    public function ValidateVacio($valor, $name = null)
    {
        if (is_array($valor)) {
            return count($valor) > 0;
        }
        if (is_object($valor)) {
            return $valor != null;
        }
        return strlen(trim($valor)) > 0;
    }

    public function ValidatePhone($phone)
    {
        $phonefilter = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        if (strlen($phonefilter) >= 7 and strlen($phonefilter) <= 13) {
            return true;
        }

        return false;
    }

    public function ValidateCellPhone($celular, $minLength = 10)
    {
        $phonefilter = filter_var($celular, FILTER_SANITIZE_NUMBER_INT);
        if (strlen($phonefilter) >= $minLength && strlen($phonefilter) <= 15) {
            return true;
        }
    }

    public function ValidateArray($array)
    {
        return is_array($array);
    }

    public function ValidateCellPhoneSize($celular, $lowerLimit, $upperLimit)
    {
        $phonefilter = filter_var($celular, FILTER_SANITIZE_NUMBER_INT);
        return (strlen($phonefilter) >= $lowerLimit && strlen($phonefilter) <= $upperLimit);
    }

    public function ValidateStringSize($value, $lowerLimit, $upperLimit)
    {
        if (strlen($value) >= $lowerLimit and strlen($value) <= $upperLimit) {
            return true;
        } else {
            return false;
        }
    }

    public function ValidatePassword($password)
    {
        if ($password != "") {

            if (strlen($password) >= 6) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function ValidateUrl($url, $name = null)
    {
        if (!empty($url)) {
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $urlfilter = filter_var($url, FILTER_VALIDATE_URL);
            if ($urlfilter) {
                return true;
            }
        }

        return false;
    }

    public function getErrorCheckout($codigo)
    {
        return [
            "error_code"=>$codigo,
            "error_message" => "Error interno en el servidor, no se logró realizar la acción",
            "error_description" => "Error interno en el servidor, no se logró realizar la acción"
        ];
    }

    public function ValidateDocument($tipodoc, $documento)
    {
        if (($this->ValidateVacio($tipodoc) == false) && ($this->ValidateVacio($documento) == false)) {
            return false;
        }

        $tipodoc = strtoupper($tipodoc);
        $pattern = "";
        $result = 0;
        switch ($tipodoc) {
            case 'NIT':
                $pattern = '/^[1-9]\d{5,8}\-?\d?$/';
                break;
            case 'CC':
                $pattern = '/^[0-9]\d{4,20}$/';
                break;
            case 'CE':
                $pattern = '/^\w{4,20}$/';
                break;
            case 'TI':
                $pattern = '/^[0-9]\d{4,20}$/';
                break;
            case 'PPN':
                $pattern = '/^\w{4,20}$/';
                break;
            case 'SSN':
                $pattern = '/^\d{3}\-?\d{2}\-?\d{4}$/';
                break;
            case 'LIC':
                $pattern = '/^\w{4,20}$/';
                break;
            case 'DNI':
                $pattern = '/^\w{4,20}$/';
                break;
        }
        if ($pattern != "") {
            $result = preg_match($pattern, $documento, $matches);
        }

        if ($result == 1) {
            return true;
        } else {
            if ($pattern == "") {
                return $pattern;
            } else {
                return false;
            }
        }

    }

    public function validateDate($fecha)
    {

        if ($fecha != "") {

            $strtofecha = explode('-', $fecha);

            $year = $strtofecha[0];
            $mes = $strtofecha[1];
            $dia = $strtofecha[2];

            if ($mes != "" and $dia != "" and $year != "") {
                try {
                    return checkdate($mes, $dia, $year);
                } catch (\Exception$e) {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function validateDateWithCarbon($date)
    {
        $date_string = (string) $date;
        $is_valid = false;

        $date_time = DateTime::createFromFormat('Y-m-d', $date_string);
        if ($date_time != false && $date_time->format('Y-m-d') === $date_string) {
            return true;
        }

        return false;

    }

    public function validateEndDate($date)
    {
        $format = strlen(explode('-', $date)[0]) == 4 ? 'Y-m-d' : 'y-m-d';
        $d = DateTime::createFromFormat($format, $date);
        if ($d) {
            $actual = DateTime::createFromFormat($format, date($format));
            date_add($actual, date_interval_create_from_date_string("30 days"));
            return strtotime($d->format($format)) <= strtotime($actual->format($format));
        } else {
            return false;
        }
    }

    public function setError($name, $mensaje)
    {
        $this->errorMessage[] = array('codError' => $name, 'errorMessage' => $mensaje);
        $this->totalerrors++;
    }

    public function validateIsSet($data, $name, $default)
    {

        if (isset($data[$name])) {
            return $data[$name];
        } else {
            return $default;
        }
    }

    public function validateBoolValue($value)
    {
        return is_bool($value);
    }
    public function validateInt($value)
    {
        return is_int($value);
    }
    public function validateOption($value, $options)
    {
        return in_array($value, $options);
    }

    public function isInteger($value)
    {
        return ctype_digit(strval($value));
    }

    public function isBase64($value)
    {
        $decoded = base64_decode($value, true);
        return $decoded === false || base64_encode($decoded) != $value ? false : true;
    }
    public function mayorZero($number)
    {
        if ($number > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function ValidateIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public function validateString($value)
    {
        return is_string($value);
    }

    public function isBoolean($value)
    {
        if (is_string($value) && ($value == 'false' || $value == 'true')) {

            return true;
        }

        return is_bool($value);
    }

    private function validateOnlyNumbers($value)
    {
        if ($this->ValidateVacio($value) == false) {
            return false;
        }
        //ONLY POSITIVE NUMBERS ALLOWED

        return (boolean) preg_match('/^[0-9]*$/', $value);

    }

    public function validateOnlyLetters($value)
    {
        if ($this->ValidateVacio($value) == false) {
            return false;
        }

        return (boolean) preg_match('/^[\p{L} ]+$/u', $value);

    }

    public function validateLength($value, $length)
    {

        if ($this->ValidateVacio($value) == false) {
            return false;
        }

        return (boolean) (strlen($value) > $length);

    }

    public function ValidateDocumentType($tipodoc)
    {
        if (($this->ValidateVacio($tipodoc) == false)) {
            return false;
        }
        $valid_documents = ['NIT', 'CC', 'CE', 'TI', 'PPN', 'SSN', 'LIC', 'DNI'];
        $tipodoc = strtoupper($tipodoc);

        return in_array($tipodoc, $valid_documents);

    }

    public function validateHttpMethod($method_value, ...$methods)
    {
        if (($this->ValidateVacio($method_value) == false) && ($this->validateOnlyNumbers($method_value) == false)) {
            return false;
        }
        $method_value = strtoupper($method_value);
        foreach ($methods as $key => &$value) {$value = strtoupper($value);}

        return in_array($method_value, $methods);

    }

    public function validateIsNumeric($value)
    {
        return is_numeric($value) && ((int) $value == $value || floatval($value) == $value);
    }
}
