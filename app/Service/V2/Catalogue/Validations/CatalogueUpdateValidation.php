<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Common\PlanSubscriptionStateCodes;
use App\Helpers\Messages\CommonText as CM;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use App\Listeners\Services\VendeConfigPlanService;
use App\Repositories\V2\CatalogueRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CatalogueUpdateValidation extends HelperPago
{

    public $response;
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function validate2(Request $request)
    {

        try
        {
            $validate = new Validate();
            $data = $request->request->all();
            if (!isset($data["ownDomainValue"])) {
                $data["ownDomainValue"] = "";
            }
            if (!isset($data["ownSubDomainValue"])) {
                $data["ownSubDomainValue"] = "";
            }
            if (!isset($data["cname"])) {
                $data["cname"] = "";
            }

            //VALIDATE ownDomain VALUE
            if (isset($data["ownDomain"])) {
                if ($data["ownDomain"]) {
                    $catalogueResult = $this->catalogueRepository->checkDomainAndSubDomainNoInMeCatalogue($data["ownDomainValue"], $data["ownSubDomainValue"], $data['id']);
                    //SET VALIDATION
                    if ($catalogueResult->count() > 0) {
                        $validate->setError(422, "the domain already exists");
                    }

                    if ($data["ownDomainValue"] != "") {
                        //CHECK IF THE DOMAIN NAME IS VALID
                        $domain_value = (string) filter_var($data["ownDomainValue"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

                        if ($domain_value === "") {
                            $validate->setError(422, "the domain is invalid");
                        }
                    }
                    if ($data["ownSubDomainValue"] != "") {
                        if (!preg_match("#^[a-zA-Z0-9]+$#", $data["ownSubDomainValue"])) {
                            $validate->setError(422, "the subdomain is invalid");
                        }
                    }
                    if (!$data["cname"] || $data["cname"] === "") {
                        $validate->setError(422, "cname is invalid");
                    }
                    $deleteOwnDomainValue = CommonValidation::validateIsSet($data, 'deleteOwnDomainValue', true, "bool");

                    if (!$this->validateIsbool($data, 'deleteOwnDomainValue')) {
                        $validate->setError(422, "deleteOwnDomainValue field should be a boolean");

                    }

                    $deleteOwnSubDomainValue = CommonValidation::validateIsSet($data, 'deleteOwnSubDomainValue', true, "bool");

                    if (!$this->validateIsbool($data, 'deleteOwnSubDomainValue')) {
                        $validate->setError(422, "deleteOwnSubDomainValue field should be a boolean");

                    }

                    $arr_respuesta['deleteOwnDomainValue'] = $deleteOwnDomainValue;
                    $arr_respuesta['deleteOwnSubDomainValue'] = $deleteOwnSubDomainValue;

                }

            }

            $clientId = CommonValidation::validateIsSet($data, 'clientId', false, 'int');
            $name = CommonValidation::validateIsSet($data, 'name', '', 'string');
            $id = CommonValidation::validateIsSet($data, 'id', '', 'int');
            $this->validateNumericParameters($data, 'id', 20, $validate);

            $finish = CommonValidation::validateIsSet($data, 'finish', false, 'bool');

            if (!$this->validateIsbool($data, 'finish')) {
                $validate->setError(422, "finish field should be a boolean");

            }

            $companyName = CommonValidation::validateIsSet($data, 'companyName', '', 'string');
            $arr_respuesta['companyName'] = $companyName;

            $ownDomain = CommonValidation::validateIsSet($data, 'ownDomain', false, 'bool');
            $arr_respuesta['ownDomain'] = $ownDomain;

            if (!$this->validateIsbool($data, 'ownDomain')) {
                $validate->setError(422, "ownDomain field should be a boolean");

            }

            CommonValidation::validateParamFormat($data, $validate, $ownDomain, 'ownDomain', 'bool', true);

            $ownDomainValue = CommonValidation::validateIsSet($data, 'ownDomainValue', null, 'string');
            $arr_respuesta['ownDomainValue'] = $ownDomainValue;
            if ($ownDomain) {
                CommonValidation::validateParamFormat($data, $validate, $ownDomainValue, 'ownDomainValue', 'empty', true);

            }

            $ownSubDomainValue = CommonValidation::validateIsSet($data, 'ownSubDomainValue', null, 'string');
            $arr_respuesta['ownSubDomainValue'] = $ownSubDomainValue;

            if ($ownDomain) {
                CommonValidation::validateParamFormat($data, $validate, $ownSubDomainValue, 'ownSubDomainValue', 'empty', true);

            }

            $contactPhone = CommonValidation::validateIsSet($data, 'contactPhone', null);
            $this->validateNumericParameters($data, 'contactPhone', 10, $validate);

            $arr_respuesta['contactPhone'] = $contactPhone;

            $contactEmail = CommonValidation::validateIsSet($data, 'contactEmail', null);
            $arr_respuesta['contactEmail'] = $contactEmail;

            if (!$finish) {
                if (!$this->isValidEmail($contactEmail)) {
                    $validate->setError(422, "contactEmail is invalid");

                }
            }

            $providerDelivery = CommonValidation::validateIsSet($data, 'providerDelivery', false);
            $arr_respuesta['providerDelivery'] = $providerDelivery;

            if (!$this->validateIsbool($data, 'providerDelivery')) {
                $validate->setError(422, "providerDelivery field should be a boolean");

            }

            $epaycoDeliveryProvider = CommonValidation::validateIsSet($data, 'epaycoDeliveryProvider', false);
            $arr_respuesta['epaycoDeliveryProvider'] = $epaycoDeliveryProvider;

            if (!$this->validateIsbool($data, 'epaycoDeliveryProvider')) {
                $validate->setError(422, "epaycoDeliveryProvider field should be a boolean");

            }

            $epaycoDeliveryProviderValues = CommonValidation::validateIsSet($data, 'epaycoDeliveryProviderValues', []);
            $arr_respuesta['epaycoDeliveryProviderValues'] = $epaycoDeliveryProviderValues;

            $senderType = CommonValidation::validateIsSet($data, 'senderType', null);
            $arr_respuesta['senderType'] = $senderType;

            $senderLastname = CommonValidation::validateIsSet($data, 'senderLastname', null);
            $arr_respuesta['senderLastname'] = $senderLastname;

            $senderFirstname = CommonValidation::validateIsSet($data, 'senderFirstname', null);
            $arr_respuesta['senderFirstname'] = $senderFirstname;

            $senderDocType = CommonValidation::validateIsSet($data, 'senderDocType', null);
            $arr_respuesta['senderDocType'] = $senderDocType;

            $senderDoc = CommonValidation::validateIsSet($data, 'senderDoc', null);
            $arr_respuesta['senderDoc'] = $senderDoc;

            $senderPhone = CommonValidation::validateIsSet($data, 'senderPhone', "");
            $arr_respuesta['senderPhone'] = $senderPhone;

            $senderBusiness = CommonValidation::validateIsSet($data, 'senderBusiness', null);
            $arr_respuesta['senderBusiness'] = $senderBusiness;

            $pickupCity = CommonValidation::validateIsSet($data, 'pickupCity', null);
            $arr_respuesta['pickupCity'] = $pickupCity;

            $pickupDepartament = CommonValidation::validateIsSet($data, 'pickupDepartament', null);
            $arr_respuesta['pickupDepartament'] = $pickupDepartament;

            $pickupAddress = CommonValidation::validateIsSet($data, 'pickupAddress', null);
            $arr_respuesta['pickupAddress'] = $pickupAddress;

            $pickupConfigurationId = CommonValidation::validateIsSet($data, 'pickupConfigurationId', null);
            $arr_respuesta['pickupConfigurationId'] = $pickupConfigurationId;

            $automaticPickup = CommonValidation::validateIsSet($data, 'automaticPickup', false);
            $arr_respuesta['automaticPickup'] = $automaticPickup;

            if (!$this->validateIsbool($data, 'automaticPickup')) {
                $validate->setError(422, "automaticPickup field should be a boolean");

            }

            $freeDelivery = CommonValidation::validateIsSet($data, 'freeDelivery', false);
            $arr_respuesta['freeDelivery'] = $freeDelivery;

            if (!$this->validateIsbool($data, 'freeDelivery')) {
                $validate->setError(422, "freeDelivery field should be a boolean");

            }

            $whatsappActive = CommonValidation::validateIsSet($data, 'whatsappActive', null);
            $arr_respuesta['whatsappActive'] = $whatsappActive;

            if (!$this->validateIsbool($data, 'whatsappActive')) {
                $validate->setError(422, "whatsappActive field should be a boolean");

            }

            $origin = CommonValidation::validateIsSet($data, 'origin', "epayco", "string");
            $arr_respuesta['origin'] = $origin;
            CommonValidation::validateParamFormat($data, $validate, $origin, 'origin', 'empty', true);

            $color = CommonValidation::validateIsSet($data, 'color', "#2227b9", "string");
            $arr_respuesta['color'] = $color;
            CommonValidation::validateParamFormat($data, $validate, $color, 'color', 'empty', true);
            if (!$this->validateHexColor($color)) {
                $validate->setError(422, "color field is invalid");

            }

            if (!$finish) {
                $banners = CommonValidation::validateIsSet($data, 'banners', null);
                $arr_respuesta['banners'] = $banners;

                foreach ($banners as $key => $value) {

                    if ($value != "" && (strlen($value) > 200 || $id === "")) {
                        if (!CommonValidation::validateBase64Image($value)) {
                            $validate->setError(422, "the banner field is invalid, invalid format");

                        }
                    }
                }

                CommonValidation::validateParamFormat($data, $validate, $banners, 'banners', 'empty', true);

                if (!empty($banners)) {
                    CommonValidation::validateParamFormat($data, $validate, $banners[0], 'banners', 'empty', true);

                }

                $img = CommonValidation::validateIsSet($data, 'image', null);
                $arr_respuesta['image'] = $img;

                if (!CommonValidation::validateBase64Image($img) && (strlen($img) > 200 || $id === "")) {
                    $validate->setError(422, "the image field is invalid, invalid format");

                }
                CommonValidation::validateParamFormat($data, $validate, $img, 'image', 'empty', true);
            }

            $currency = CommonValidation::validateIsSet($data, 'currency', "COP", "string");
            $arr_respuesta['currency'] = $currency;
            CommonValidation::validateParamFormat($data, $validate, $currency, 'currency', 'empty', true);

            if (is_string($currency)) {
                if (strlen($currency) > 4) {
                    $validate->setError(422, "currency field must not be greater than 4 characters");

                }

            } else {
                $validate->setError(422, "currency field should be a string");

            }

            $default_language = CommonValidation::validateIsSet($data, 'default_language', "ESP", "string");
            $arr_respuesta['default_language'] = $default_language;
            CommonValidation::validateParamFormat($data, $validate, $default_language, 'default_language', 'empty', true);

            if (is_string($default_language)) {
                if (strlen($default_language) > 5) {
                    $validate->setError(422, "default_language field must not be greater than 5 characters");

                }

            } else {
                $validate->setError(422, "default_language field should be a string");

            }

            $progress = CommonValidation::validateIsSet($data, 'progress', null);

            $active = CommonValidation::validateIsSet($data, 'active', false, 'bool');
            CommonValidation::validateParamFormat($data, $validate, $active, 'active', 'bool', true);

            if (!$this->validateIsbool($data, 'active')) {
                $validate->setError(422, "active field should be a boolean");

            }

            $indicativoPais = CommonValidation::validateIsSet($data, 'indicativoPais', 57);
            CommonValidation::validateParamFormat($data, $validate, $indicativoPais, 'indicativoPais', 'empty', true);
            $this->validateNumericParameters($data, 'indicativoPais', 4, $validate);

            $cname = CommonValidation::validateIsSet($data, 'cname', null, 'string');
            $arr_respuesta['cname'] = $cname;
            if ($ownDomain) {
                CommonValidation::validateParamFormat($data, $validate, $cname, 'cname', 'empty', true);

            }

            $arr_respuesta['progress'] = $progress;
            $arr_respuesta['active'] = $active;
            $arr_respuesta['indicativoPais'] = $indicativoPais;

            $analytics = CommonValidation::validateIsSet($data, 'analytics', []);

            if (is_array($analytics)) {
                $analytics = isset($data["analytics"]) ? $data["analytics"] : [];
                $analytics = (object) $analytics;
            } else if (!is_object($analytics)) {
                $validate->setError(422, "field analytics is type object");
            }
            $this->validateAnalyticsItems($analytics, $validate);

            $arr_respuesta["analytics"] = $analytics;

            CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', 'empty');

            CommonValidation::validateParamFormat($arr_respuesta, $validate, $name, 'name', 'range', true, [1, 50]);
            CommonValidation::validateParamFormat($arr_respuesta, $validate, $name, 'name', 'empty', true);

            CommonValidation::validateParamFormat($arr_respuesta, $validate, $id, 'id', 'empty');

            if (isset($origin) && $origin == CM::ORIGIN_EPAYCO && !$finish) {

                CommonValidation::validateParamFormat($arr_respuesta, $validate, $companyName, 'companyName', 'empty', true);
                CommonValidation::validateParamFormat($arr_respuesta, $validate, $companyName, 'companyName', 'range', true, [1, 50]);

                CommonValidation::validateParamFormat($arr_respuesta, $validate, $contactPhone, 'contactPhone', 'phone', true);
                CommonValidation::validateParamFormat($arr_respuesta, $validate, $contactEmail, 'contactEmail', 'range', true, [1, 50]);

                $this->validateQuantityBbl($id, $active, $validate, $clientId, $progress);

                CommonValidation::validateParamFormat($arr_respuesta, $validate, $providerDelivery, 'providerDelivery', 'boolean', false);
                CommonValidation::validateParamFormat($arr_respuesta, $validate, $epaycoDeliveryProvider, 'epaycoDeliveryProvider', 'boolean', false);

                if ($epaycoDeliveryProvider) {
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderType, 'senderType', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderType, 'senderType', 'range', false, [1, 20]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderDocType, 'senderDocType', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderDoc, 'senderDoc', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderDoc, 'senderDoc', 'range', false, [1, 20]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderPhone, 'senderPhone', 'empty', false);
                    if (isset($data["senderPhone"])) {
                        $data["senderPhone"] = ($data["senderPhone"] == null || $data["senderPhone"] == "null") ? "" : $data["senderPhone"];

                    }

                    $this->validateNumericParameters($data, 'senderPhone', 20, $validate);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupCity, 'pickupCity', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupCity, 'pickupCity', 'range', false, [1, 50]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupDepartament, 'pickupDepartament', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupDepartament, 'pickupDepartament', 'range', false, [1, 50]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupAddress, 'pickupAddress', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupAddress, 'pickupAddress', 'range', false, [1, 150]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupConfigurationId, 'pickupConfigurationId', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $pickupConfigurationId, 'pickupConfigurationId', 'range', false, [1, 100]);

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $automaticPickup, 'automaticPickup', "boolean", false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $freeDelivery, 'freeDelivery', "boolean", false);
                }
                if ($epaycoDeliveryProvider && $senderType == "Personal") {
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderFirstname, 'senderFirstname', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderFirstname, 'senderFirstname', 'range', false, [1, 50]);

                    if (!$this->validateAlphabeticString($senderFirstname)) {
                        $validate->setError(422, "senderFirstname field can't contain numeric values");

                    }

                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderLastname, 'senderLastname', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderLastname, 'senderLastname', 'range', false, [1, 50]);

                    if (!$this->validateAlphabeticString($senderLastname)) {
                        $validate->setError(422, "senderLastname field can't contain numeric values");

                    }

                } else if ($epaycoDeliveryProvider) {
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderBusiness, 'senderBusiness', 'empty', false);
                    CommonValidation::validateParamFormat($arr_respuesta, $validate, $senderBusiness, 'senderBusiness', 'range', false, [1, 50]);

                }
                if ($epaycoDeliveryProvider && empty($epaycoDeliveryProviderValues)) {
                    $validate->setError(422, "field epaycoDeliveryProviderValues you can't be empty");
                }
            } else {
                $arr_respuesta["finish"] = $finish;
            }

            $validateForbiddenWordService = Container::getInstance()->make(\App\Service\V2\ForbiddenWord\Process\ValidateForbiddenWordService::class);

            $has_errors = $validateForbiddenWordService->handle(["nombre" => $name, "nombre_empresa" => $companyName], ["endpoint_action" => "actualizar", "action" => "Tienda"]);
            if (!$has_errors["success"]) {

                $logService = Container::getInstance()->make(\App\Service\V2\MongoLog\Process\MongoLogService::class);

                $logService->handle(["module" => "Tienda", "action" => "actualizar", "client_id" => $clientId, "word" => $has_errors["word"]]);

                return $has_errors;
            }

            if ($validate->totalerrors > 0) {
                $success = false;
                $last_action = 'validation data save';
                $title_response = 'Error';
                $text_response = 'Some fields are required, please correct the errors and try again';

                $data = [
                    'totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage,
                ];
                $response = [
                    'success' => $success,
                    'titleResponse' => $title_response,
                    'textResponse' => $text_response,
                    'lastAction' => $last_action,
                    'data' => $data,
                ];

                return $this->response = $response;
            }

            $response = [];
            $response['success'] = true;
            $response['data'] = $arr_respuesta;
            $response['titleResponse'] = "Catalogue is valid";
            $response['textResponse'] = "Catalogue is valid";
            $this->response = $arr_respuesta;
            return $response;

        } catch (\Exception $e) {
            Log::info($e);
        }
    }


    private function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    private function validateIsbool($data, $value)
    {
        if (isset($data[$value])) {
            return is_bool($data[$value]);
        }
        return true;
    }

    private function validateAlphabeticString($string)
    {
        return preg_match('/^[a-zA-Z ]+$/', $string);
    }

    private function validateHexColor($hex)
    {
        return strlen($hex) >= 6 && ctype_xdigit(substr($hex, 1));
    }

    private function validateQuantityBbl($id, $activo, $validate, $clientId, $progress)
    {
        //instancio el servicio
        $vendeConfigPlan = new VendeConfigPlanService();
        $configVende = $vendeConfigPlan->validatePlan($clientId);
        if (!$configVende) {
            //el codigo 100002 es para identificar el error del plan no activo ni renovado al el cliente (dashboard)
            return $validate->setError(10002, CM::PLAN_CANCEL);
        }

        $totalAllCatalogs = $vendeConfigPlan->getTotalActiveCatalogsV2($clientId, CM::ORIGIN_EPAYCO, null);
        $totalCatalogs = $vendeConfigPlan->getTotalActiveCatalogsV2($clientId, CM::ORIGIN_EPAYCO, null, true, true);
        $oldCatalogue = !$id ? null : $vendeConfigPlan->getTotalActiveCatalogsV2($clientId, CM::ORIGIN_EPAYCO, $id);
        $totalCatalogs = $totalCatalogs ? count($totalCatalogs) : 0;
        $totalAllCatalogs = $totalAllCatalogs ? count($totalAllCatalogs) : 0;

        //si el catalogo es nuevo y ya posee el limite de catalogos activos ó
        //si lo que desea es activar un catalogo anterior ya teniendo el limite de catalogos activos

        if (
            ($id == 0 &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $configVende['allowedCatalogs'] <= $totalCatalogs &&
                $configVende["planState"] == PlanSubscriptionStateCodes::ACTIVE
            )
            ||
            ($id !== 0 &&
                $activo &&
                $configVende['allowedCatalogs'] != 'ilimitado' &&
                $oldCatalogue && !$oldCatalogue[0]['activo'] &&
                $totalCatalogs >= $configVende['allowedCatalogs'] &&
                $configVende["planState"] == PlanSubscriptionStateCodes::ACTIVE
            )
            ||
            ($configVende["planState"] == PlanSubscriptionStateCodes::INTEGRATION &&
                ($progress == "publicado" || ($id == 0 && $totalAllCatalogs >= 1))
            )
        ) {
            //el codigo 100001 es para identificar el error por exceder los limites del plan en el cliente (dashboard)
            $validate->setError(100001, CM::PLAN_EXCEEDED);
        }
    }

    private function validateAnalyticsItems($analytics, $validate)
    {

        $analytics = (array) $analytics;

        if (!empty($analytics)) {

            $paramsMustBeBoolean = [
                "facebookPixelActive",
                "googleAnalyticsActive",
                "googleTagManagerActive",
            ];

            $paramsMustBeString = [
                "facebookPixelId",
                "googleAnalyticsId",
                "googleTagManagerId",
            ];

            $this->validateParamsInAnalytics($analytics, $paramsMustBeBoolean, $validate, "boolean");
            $this->validateParamsInAnalytics($analytics, $paramsMustBeString, $validate, "string");
        }
    }

    private function validateParamsInAnalytics($analytics, $paramsMustBe, $validate, $mustBeType)
    {
        foreach ($paramsMustBe as $paramMustBe) {
            if (!isset($analytics[$paramMustBe])) {
                $validate->setError(422, "field analytics." . $paramMustBe . " is required");
            } else {
                if (
                    ($mustBeType == "boolean" && !is_bool($analytics[$paramMustBe]) ||
                        ($mustBeType == "string" && !is_string($analytics[$paramMustBe])))
                ) {
                    $validate->setError(422, "field analytics." . $paramMustBe . " must be " . $mustBeType);
                }
            }
        }
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
