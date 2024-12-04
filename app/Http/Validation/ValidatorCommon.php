<?php

namespace App\Http\Validation;

use App\Helpers\Bin\HelperBins;
use App\Helpers\Pago\HelperPago;

class ValidatorCommon extends HelperPago
{
    //code errores
    const A001 = 'A001';
    const A002 = 'A002';
    const A003 = 'A003';
    const A008 = 'A008';
    const E012 = 'E012';
    const A009 = 'A009';

    //type validations
    const EMPTY = 'empty';
    const STRING = 'string';
    const PHONE = 'phone';
    const EMAIL = 'email';
    const RANGE = 'range';
    const INT = 'int';
    const BOOL = 'bool';
    const FLOAT = 'float';
    const DATE = 'date';
    const NUMERIC_RANGE = 'numericRange';
    const ARRAY = 'array';
    const IP = 'ip';
    const CARD = 'card';
    const BIN = 'bin';
    const ENDDATE = 'endDate';
    const NOT_VALIDATE = 'not validate';

    /**
     * @var Validate
     */
    public $validate;

    public $errorA001;
    public $errorA002;
    public $errorA003;
    public $errorA008;
    public $errorE012;
    public $errorA009;

    /**
     * ValidatorCommon constructor.
     * @param Validate $validate
     */
    public function __construct(
        Validate $validate
    ) {
        $this->validate = $validate;

        $this->errorA001 = $this->validate->getErrorCheckout(self::A001);
        $this->errorA002 = $this->validate->getErrorCheckout(self::A002);
        $this->errorA003 = $this->validate->getErrorCheckout(self::A003);
        $this->errorA008 = $this->validate->getErrorCheckout(self::A008);
        $this->errorE012 = $this->validate->getErrorCheckout(self::E012);
        $this->errorA009 = $this->validate->getErrorCheckout(self::A009);
    }

    /**
     * @param array $response
     * @param $param
     * @param string $name
     * @param string $validateType
     * @param bool $required
     * @param int[] $range
     */
    public function validateParamFormat(
        array &$response,
        $param,
        string $name,
        string $validateType,
        $required = true,
        $range = [0,0]
    ){
        if (isset($param)) {
            switch ($validateType) {
                case self::EMPTY:
                    $validateParam= $this->validate->ValidateVacio($param, $name);
                    if (!$validateParam) {
                        $this->validate->setError(
                            self::A001,
                            trans("error.{$this->errorA001->error_message}", ['field' => $name])
                        );
                    }
                    break;
                case self::PHONE:
                    $validateParam = $this->validate->ValidatePhone($param);
                    if (!$validateParam) {
                        $this->validate->setError(
                            self::A003,
                            trans("error.{$this->errorA003->error_message}", ['field' => $name, 'maxLength' => 13])
                        );
                    }
                    break;
                case self::EMAIL:
                    if (!filter_var($param, FILTER_VALIDATE_EMAIL)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'email'])
                        );
                    }
                    break;
                case self::RANGE:
                    $validateParam = $this->validate->ValidateStringSize($param, $range[0], $range[1]);
                    if (!$validateParam) {
                        $this->validate->setError(
                            self::A003,
                            trans("error.{$this->errorA003->error_message}", ['field' => $name, 'maxLength' => $range[1]])
                        );
                    }
                    break;
                case self::STRING:
                    if (!is_string($param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'string'])
                        );
                    }
                    break;
                case self::INT:
                    if (!is_int($param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'integer'])
                        );
                    }
                    break;
                case self::FLOAT:
                    if (!is_float($param) || !is_numeric($param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'float'])
                        );
                    }
                    break;
                case self::BOOL:
                    if (!is_bool($param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'boolean'])
                        );
                    }
                    break;
                case self::NUMERIC_RANGE:
                    if (!$this->validateNumericRange($param, $range[0], $range[1])){
                        $this->validate->setError(
                            self::A002,
                            trans(
                                "error.{$this->errorA002->error_message}",
                                ['field' => $name, 'fieldType' => 'between: '. $range[0] . '-' . $range[1]]
                            )
                        );
                    }
                    break;
                case self::DATE:
                    if(!$this->validate->validateDate($param, 'date')){
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'date'])
                        );
                    }
                    break;
                case self::ARRAY:
                    if (!is_array($param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'array'])
                        );
                    }
                    break;
                case self::IP:
                    if (!preg_match('/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/', $param)) {
                        $this->validate->setError(
                            self::A002,
                            trans("error.{$this->errorA002->error_message}", ['field' => $name, 'fieldType' => 'ip'])
                        );
                    }
                    break;
                case self::CARD:
                    $paramLength = intval(ceil(log10($param)));
                    $binCodeInitial = substr($param, 0, 6);
                    $exactBin = HelperBins::getBin(intval($binCodeInitial));

                    if ($paramLength < 15 || $paramLength >= 20) {
                        $this->validate->setError(
                            self::A003,
                            trans("error.{$this->errorA003->error_message}", ['field' => $name, 'fieldType' => 'card'])
                        );
                    }
                    if ($exactBin['meta']['status'] === '200' && $exactBin["data"]['bin'] === 'INVALID BIN') {
                        $this->validate->setError(
                            self::E012,
                            trans("error.{$this->errorE012->error_message}, field: $name, value: $param", ['field' => $name, 'fieldType' => 'card', 'value'])
                        );
                    }
                    break;
                case self::BIN:
                    $paramLength = intval(ceil(log10($param)));
                    $exactBin = HelperBins::getBin(intval($param));
                    if ($paramLength != 6) {
                        $this->validate->setError(
                            self::A003,
                            trans("error.{$this->errorA003->error_message}", ['field' => $name, 'fieldType' => 'bin'])
                        );
                    }
                    if ($exactBin['meta']['status'] === '200' && $exactBin["data"]['bin'] === 'INVALID BIN') {
                        $this->validate->setError(
                            self::E012,
                            trans("error.{$this->errorE012->error_message}, field: $name, value: $param", ['field' => $name, 'fieldType' => 'bin', 'value'])
                        );
                    }
                    break;
                case 'endDate':
                    if(!$this->validate->validateEndDate($param)){
                        $this->validate->setError(
                            self::A008,
                            trans("error.{$this->errorA008->error_message}", ['field' => $name, 'maxDays' => '30'])
                        );
                    }
                default:
                    break;
            }

            $response[$name] = $param;
        } else {
            if($required){
                $this->validate->setError(
                    self::A001,
                    trans("error.{$this->errorA001->error_message}", ['field' => $name])
                );
            }
        }
    }

    /**
     * @param int value
     * @param int total
     * @param int type
     */
     public function validateMaxValue($value, $total, $type, $name){
        if($type === 1) { // fijo
            if($value > $total){
                $this->validate->setError(
                    self::A009,
                    trans("error.{$this->errorA009->error_message}", ['field' => $name, 'value' => $total])
                );
            }
        }else {
            if($value > 100){
                $this->validate->setError(
                    self::A009,
                    trans("error.{$this->errorA009->error_message}", ['field' => $name, 'value' => "100 %"])
                );
            }
        }
     }

    /**
     * @param array $data
     * @param string $key
     * @param string $cast
     * @param null $default
     * @param false $noZero
     * @return mixed|null
     */
    public function validateIsSet(
        array $data,
        string $key,
        string $cast = "",
        $default = null,
        $noZero = false
    ){
        $content = $default;
        if (isset($data[$key])) {
            switch ($cast) {
                case self::INT:
                    $content = (int) $data[$key];
                    break;
                case self::FLOAT:
                    $content = (float) $data[$key];
                    if($noZero && $content === 0){
                        $content = "";
                    }
                    break;
                case self::STRING:
                    $content = (string) $data[$key];
                    break;
                case self::BOOL:
                    $content = is_bool($data[$key]) ? (bool) $data[$key] : $data[$key];
                    break;
                case self::ARRAY:
                    $content = (array) $data[$key];
                    break;
                default:
                    $content = null;
            }
        }

        return $content;

    }

    /**
     * @param string $logDescription
     * @param int|null $clientId
     * @return array|null
     */
    public function responseErrors(string $logDescription = '', int $clientId = null): ?array
    {
        if ($this->validate->totalerrors > 0) {
            $response = array(
                'success' => false,
                'titleResponse' => 'Error',
                'textResponse' => trans('message.Some fields are required, please correct the errors and try again'),
                'lastAction' => 'validation data',
                'data' => [
                    'totalErrors' => $this->validate->totalerrors,
                    'errors' => $this->validate->errorMessage
                ],
            );

            if ($clientId !== null) {
                $this->saveLog(2, $clientId, '', json_encode($response), $logDescription);
            }

            return $response;
        }
        return null;
    }

    /**
     * @param string $errorCode
     * @param string $parameterName
     * @param string $fieldType
     */
    public function customErrors(string $errorCode, string $parameterName, string $fieldType)
    {
        $error = $this->validate->getErrorCheckout($errorCode);
        $this->validate->setError(
            $errorCode,
            trans("error.{$error->error_message}", ['field' => $parameterName, 'fieldType' => $fieldType])
        );
    }

    /**
     * @param string $errorCode
     * @param string $parameterName
     * @param string $fieldType
     */
    public function customErrorMessage(string $errorCode, string $message)
    {
        $this->validate->setError(
            $errorCode,
            $message
        );
    }

    private function validateNumericRange(float $value, float $min, float $max)
    {
        return ($min <= $value) && ($value <= $max);
    }
}