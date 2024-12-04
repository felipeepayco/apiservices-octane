<?php
namespace App\Service\V2\Product\Validations;

use App\Helpers\Messages\CommonText as CM;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Validation\ValidateError;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\CategoryRepository;
use App\Repositories\V2\ProductRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateProductValidation
{
    const EMPTY = 'empty';
    public $response;

    protected ProductRepository $productRepository;
    protected CatalogueRepository $catalogueRepository;
    protected CategoryRepository $categoryRepository;

    private $catalog;

    public function __construct(ProductRepository $productRepository, CatalogueRepository $catalogueRepository, CategoryRepository $categoryRepository)
    {
        $this->productRepository = $productRepository;
        $this->catalogueRepository = $catalogueRepository;
        $this->categoryRepository = $categoryRepository;

    }
    public function validate(Request $request)
    {

        try {
            $validate = new Validate();
            $data = $request->all();
            $this->response = [];

            $clientId = CommonValidation::validateIsSet($data, 'clientId', false, 'int');
            CommonValidation::validateParamFormat($this->response, $validate, $clientId, 'clientId', self::EMPTY);
            $origin = CommonValidation::validateIsSet($data, 'origin', "epayco", "string");
            CommonValidation::validateParamFormat($this->response, $validate, $origin, 'origin', self::EMPTY, true);

            $this->response['origin'] = $origin;

            $catalogueId = CommonValidation::validateIsSet($data, 'catalogueId', '', 'int');
            CommonValidation::validateParamFormat($this->response, $validate, $catalogueId, 'catalogueId', self::EMPTY);
            $this->catalog = $this->catalogueRepository->find($catalogueId);

            $this->validateNumericParameters($data, 'catalogueId', 20, $validate);

            $id = isset($data["id"]) ? $data["id"] : "";
            $id = ($id == 0) ? "" : $id;
            $data["id"] = $id;
            $this->response["id"] = $id;

            $this->validateNumericParameters($data, 'id', 20, $validate);

            $moneda = CommonValidation::validateIsSet($data, 'currency', '', 'string');
            CommonValidation::validateParamFormat($this->response, $validate, $moneda, 'currency', self::EMPTY, true);

            if (!preg_match('/^[\pL\s\-]+$/u', $moneda)) {
                $validate->setError(422, "currency field is invalid, alphanumeric value expected");
            }

            $valor = CommonValidation::validateIsSet($data, 'amount', '', 'float', true);
            CommonValidation::validateParamFormat($this->response, $validate, $valor, 'amount', self::EMPTY, true);

            $this->validateNumericParameters($data, 'amount', 20, $validate);

            $referencia = CommonValidation::validateIsSet($data, 'reference', "");
            $this->response["reference"] = $referencia;

            $cobrounico = 0;
            $this->response["onePayment"] = $cobrounico;

            $cantidad = CommonValidation::validateIsSet($data, 'quantity', '', 'int');
            CommonValidation::validateParamFormat($this->response, $validate, $cantidad, 'quantity', self::EMPTY, true);

            if (count($data["productReferences"]) == 0) {
                $this->validateNumericParameters($data, 'quantity', 10, $validate);

            }

            if ($origin != "epayco" && !$validate->mayorZero($cantidad) && trim($id) != "" && $id == 0) {
                $validate->setError(422, CM::FIELD . ' quantity is invalid');

            }

            $disponibles = $cantidad;
            if ($id > 0) {
                $disponibles = $disponibles != "" ? (int) $disponibles : $cantidad;
            }
            $this->response["available"] = $disponibles;

            $urlConfirmacion = CommonValidation::validateIsSet($data, 'urlConfirmation', "");
            $this->response["urlConfirmation"] = $urlConfirmacion;

            $urlRespuesta = CommonValidation::validateIsSet($data, 'urlResponse', "");
            $this->response["urlResponse"] = $urlRespuesta;

            $discountRate = CommonValidation::validateIsSet($data, 'discountRate', 0, "float");
            $this->response["discountRate"] = $discountRate;

            $this->validateNumericParameters($data, 'discountRate', 20, $validate, true);

            $iva = CommonValidation::validateIsSet($data, 'tax', 0, "float");
            $this->response["tax"] = $iva;

            $this->validateNumericParameters($data, 'tax', 20, $validate, true);

            $base = CommonValidation::validateIsSet($data, 'baseTax', 0, 'float');
            $this->response["baseTax"] = $base;

            $active = CommonValidation::validateIsSet($data, 'active', true, 'bool');
            $this->response['active'] = $active;

            if (isset($data["active"])) {
                if ($data["active"] != "") {
                    if (!is_bool($data["active"])) {
                        $validate->setError(422, "active field is invalid, boolean value expected");

                    }
                }
            }

            $activeTax = CommonValidation::validateIsSet($data, 'activeTax', null, 'bool');
            $this->response['activeTax'] = $activeTax;

            if (isset($data["activeTax"])) {
                if ($data["activeTax"] != "") {
                    if (!is_bool($data["activeTax"])) {
                        $validate->setError(422, "activeTax field is invalid, boolean value expected");

                    }
                }
            }

            if ($activeTax) {
                if ($iva < 1) {
                    $validate->setError(422, "tax field must be greater than 0");

                }
            }

            $consumptionTax = CommonValidation::validateIsSet($data, 'consumptionTax', 0, 'float');
            $this->response['consumptionTax'] = $consumptionTax;

            $this->validateNumericParameters($data, 'consumptionTax', 20, $validate, true);

            $activeConsumptionTax = CommonValidation::validateIsSet($data, 'activeConsumptionTax', false, 'bool');
            $this->response['activeConsumptionTax'] = $activeConsumptionTax;

            if (isset($data["activeConsumptionTax"])) {
                if ($data["activeConsumptionTax"] != "") {
                    if (!is_bool($data["activeConsumptionTax"])) {
                        $validate->setError(422, "activeConsumptionTax field is invalid, boolean value expected");

                    }
                }
            }

            if ($activeConsumptionTax) {
                if ($consumptionTax < 1) {
                    $validate->setError(422, "consumptionTax field must be greater than 0");

                }
            }

            $title = CommonValidation::validateIsSet($data, 'title', '', 'string');
            CommonValidation::validateParamFormat($this->response, $validate, $title, 'title', 'range', true, [1, 50]);
            CommonValidation::validateParamFormat($this->response, $validate, $title, 'title', self::EMPTY, true);

            $descripcion = CommonValidation::validateIsSet($data, 'description', '', 'string');
            CommonValidation::validateParamFormat($this->response, $validate, $descripcion, 'description', 'range', false, [0, 800]);
            CommonValidation::validateParamFormat($this->response, $validate, $descripcion, 'description', 'empty', true);

            $shippingTypes = CommonValidation::validateIsSet($data, 'shippingTypes', []);
            $this->response['shippingTypes'] = $shippingTypes;

            if (isset($data["shippingTypes"])) {
                if ($data["shippingTypes"] != "") {
                    if (!is_array($data["shippingTypes"])) {
                        $validate->setError(422, "shippingTypes field is invalid");

                    }
                }
            }

            $fechavencimiento = CommonValidation::validateIsSet($data, 'expirationDate', null);
            CommonValidation::validateDateFormat($this->response, $validate, $fechavencimiento, 'expirationDate', false);

            $img = CommonValidation::validateIsSet($data, 'img', null);
            CommonValidation::validateParamFormat($this->response, $validate, $img, 'img', 'empty', true);

            if (!empty($img)) {
                CommonValidation::validateParamFormat($this->response, $validate, $img[0], 'img', 'empty', true);

            }
            foreach ($data["img"] as $key => $value) 
            {

                if($value !="" && (strlen($value) > 200 || $id === ""))
                {
                    if (!CommonValidation::validateBase64Image($value)) {
                        $validate->setError(422, "the img field is invalid, invalid format");
        
                    }
                }
          
            }
         

            $this->response["img"] = $img;

            $contactName = CommonValidation::validateIsSet($data, 'contactName', "", "string");
            $this->response["contactName"] = $contactName;

            $contactNumber = CommonValidation::validateIsSet($data, 'contactNumber', "", "string");
            $this->response["contactNumber"] = $contactNumber;

            $productReferences = CommonValidation::validateIsSet($data, 'productReferences', null);

            $setupReferences = CommonValidation::validateIsSet($data, 'setupReferences', []);
            $showInventory = CommonValidation::validateIsSet($data, 'showInventory', false);
            $this->response['showInventory'] = $showInventory;

            $discountPrice = CommonValidation::validateIsSet($data, 'discountPrice', 0, 'float');
            $this->response['discountPrice'] = $discountPrice;
            $this->validateNumericParameters($data, 'discountPrice', 20, $validate);

            if ($discountRate != 0) {
                if ($discountPrice == 0) {
                    $validate->setError(422, "field discountPrice must be greater than 0");

                }

            }

            $categories = CommonValidation::validateIsSet($data, 'categories', null);
            $this->response['categories'] = $categories;
            $this->validateNumericParameters($data, 'categories', 20, $validate);

            if (!count($this->categoryRepository->categoriesInCatalogue($clientId, $categories))) {

                $validate->setError(422, "Category not found");
            }

            $outstanding = CommonValidation::validateIsSet($data, 'outstanding', false, 'bool');
            $this->response['outstanding'] = $outstanding;

            $epaycoDeliveryProvider = CommonValidation::validateIsSet($data, 'epaycoDeliveryProvider', false);
            $this->response['epaycoDeliveryProvider'] = $epaycoDeliveryProvider;

            $epaycoDeliveryProviderValues = CommonValidation::validateIsSet($data, 'epaycoDeliveryProviderValues', []);
            $this->response['epaycoDeliveryProviderValues'] = $epaycoDeliveryProviderValues;

            if ($productReferences !== null && is_array($productReferences) && count($productReferences) > 0) {
                $this->validateProductsReferences($validate, 'product', $productReferences, $id);
                $this->validateProductsReferences($validate, 'setupReferences', $setupReferences, $id);
                $this->validateReferences($validate, $setupReferences, 'setupReferences');

                $this->response["productReferences"] = $productReferences;
                $this->response['setupReferences'] = $setupReferences;
            }

            $netAmount = CommonValidation::validateIsSet($data, 'netAmount', "", 'float', true);
            $this->validateNumericParameters($data, 'netAmount', 20, $validate);

            CommonValidation::validateParamFormat($this->response, $validate, $netAmount, 'netAmount', self::EMPTY, true);
            $realWeight = CommonValidation::validateIsSet($data, 'realWeight', 0, 'float', false);
            $this->validateMeasures($data, 'realWeight', 20, $validate);

            $high = CommonValidation::validateIsSet($data, 'high', 0, 'float', false);
            $this->validateMeasures($data, 'high', 20, $validate);

            $long = CommonValidation::validateIsSet($data, 'long', 0, 'float', false);
            $this->validateMeasures($data, 'long', 20, $validate);

            $width = CommonValidation::validateIsSet($data, 'width', 0, 'float', false);
            $this->validateMeasures($data, 'width', 20, $validate);

            $declaredValue = CommonValidation::validateIsSet($data, 'declaredValue', 0, 'float', false);
            if (isset($this->catalog->epayco_logistica)) {
                if ($this->catalog->epayco_logistica) {
                    $this->validateNumericParameters($data, 'declaredValue', 20, $validate);

                }

            }

            CommonValidation::validateParamFormat($this->response, $validate, $realWeight, 'realWeight', self::EMPTY, false);
            CommonValidation::validateParamFormat($this->response, $validate, $high, 'high', self::EMPTY, false);
            CommonValidation::validateParamFormat($this->response, $validate, $long, 'long', self::EMPTY, false);
            CommonValidation::validateParamFormat($this->response, $validate, $width, 'width', self::EMPTY, false);
            CommonValidation::validateParamFormat($this->response, $validate, $declaredValue, 'declaredValue', self::EMPTY, false);
            $this->validateCategories($validate, $categories);
            $this->validateQuantity($id, $active, $validate, $clientId);
            if ($epaycoDeliveryProvider && empty($epaycoDeliveryProviderValues)) {
                $validate->setError(422, "field epaycoDeliveryProviderValues you can't be empty");
            }

            $this->validateProductTitle($id, $title, $validate, $clientId);

            if ($validate->totalerrors > 0) {
                $arr_respuesta['success'] = false;
                $this->response = ValidateError::validateError($validate);

                $response = [];
                $response['success'] = true;
                $response['success_validation'] = false;
                $response['data'] = $this->response["data"];
                $response['titleResponse'] = "Product is invalid";
                $response['textResponse'] = "Product is invalid";

                return $response;

            }

            $validateForbiddenWordService = Container::getInstance()->make(\App\Service\V2\ForbiddenWord\Process\ValidateForbiddenWordService::class);

            $has_errors = $validateForbiddenWordService->handle(["titulo" => $title, "descripcion" => $descripcion], ["endpoint_action" => ($id) ? "actualizar" : "crear", "action" => "Producto"]);
            if (!$has_errors["success"]) {

                $logService = Container::getInstance()->make(\App\Service\V2\MongoLog\Process\MongoLogService::class);

                $logService->handle(["module" => "Producto", "action" => ($id) ? "actualizar" : "crear", "client_id" => $clientId, "word" => $has_errors["word"]]);

                $has_errors["success_validation"] = false;
                $has_errors["success"] = true;
                return $has_errors;
            }
            //Nueva categoria
            if (isset($data["category_new"])) {
                $this->response['category_new'] = $data["category_new"];
            }

            $response = [];
            $response['success'] = true;
            $response['success_validation'] = true;
            $response['data'] = $this->response;
            $response['titleResponse'] = "Product is valid";
            $response['textResponse'] = "Product is valid";

            return $response;
        } catch (\Exception $e) {
            Log::info($e);

        }
    }

    private function validateMeasures($data, $parameter_name, $length, $validate)
    {
        if (isset($this->catalog->epayco_logistica)) {
            if ($this->catalog->epayco_logistica) {
                if (isset($data[$parameter_name])) {

                    if ($data[$parameter_name] != "0") {
                        if (!$validate->validateIsNumeric($data[$parameter_name])) {

                            $validate->setError(422, "{$parameter_name} field is invalid, numeric value expected");
                        } else {
                            $parameter_length = floor(log10(abs($data[$parameter_name]))) + 1;

                            if ($parameter_length > $length) {

                                $validate->setError(422, "$parameter_name field can not be greater than 20 digits");

                            }

                            if ($data[$parameter_name] < 1) {

                                $validate->setError(422, "$parameter_name field can not be negative");

                            }
                        }
                    }

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

    private function validateProductsReferences($validate, $type, $productReferences, $id)
    {

        $paramsMustBeNumber = [
            "netAmount",
            "amount",
            "discountPrice",
            "quantity",
            "discountRate",
        ];
        foreach ($productReferences as $item) {
            if ($type === 'product') {
                if($item["img"] && ((is_string($item["img"]) && strlen($item["img"]) > 200) || $id === ""))
                {
                    if (!CommonValidation::validateBase64Image($item["img"])) {
                        $validate->setError(422, "the reference img field is invalid, invalid format");
    
                    }
                }
           

                $this->validateParamsInReferences($item, $paramsMustBeNumber, $validate, "number", $type);
                $this->validateParamsInReferences($item, ["name"], $validate, "string", $type);
                $this->validateParamsInReferences($item, ["img"], $validate, "string", $type, false);
            } else {
                $this->validateParamsInReferences($item, ["name"], $validate, "string", $type);
                $this->validateParamsInReferences($item, ["type"], $validate, "string", $type);
                $this->validateParamsInReferences($item, ["values"], $validate, "array", $type);
            }
        }

    }

    private function validateParamsInReferences($product, $paramsMustBe, $validate, $mustBeType, $type, $required = true)
    {
        foreach ($paramsMustBe as $paramMustBe) {
            if ($required && !isset($product[$paramMustBe])) {
                $validate->setError(422, "field " . $type . "." . $paramMustBe . " is required");
            } else {
                if ($required && (($mustBeType == "number" && !is_numeric($product[$paramMustBe])) || ($mustBeType == "string" && !is_string($product[$paramMustBe])) || ($mustBeType == "array" && !is_array($product[$paramMustBe])))) {
                    $validate->setError(422, "field " . $type . "." . $paramMustBe . " must be " . $mustBeType);
                }
            }
        }

    }

    private function validateReferences($validate, $references, $type)
    {
        $validateForbiddenWordService = Container::getInstance()->make(\App\Service\V2\ForbiddenWord\Process\ValidateForbiddenWordService::class);
        switch ($type) {

            case 'setupReferences':

                foreach ($references as $r) {

                    $end_string = ($r["type"] == "otra" || $r["type"] == "talla") ? "permitida" : "permitido";

                    if ($r["type"] == "otra") {
                        $has_errors = $validateForbiddenWordService->handle(["value" => $r["name"]], ["endpoint_action" => "crear", "action" => "Producto referencia"]);
                        if (!$has_errors["success"]) {
                            $validate->setError('FDWE100', "Propiedad " . $r["type"] . ": " . $r["name"] . " no es " . $end_string);
                        }
                    }

                    foreach ($r["values"] as $p) {
                        $has_errors = $validateForbiddenWordService->handle(["value" => $p], ["endpoint_action" => "crear", "action" => "Producto referencia"]);

                        if (!$has_errors["success"]) {
                            $validate->setError('FDWE100', "Propiedad " . $r["type"] . ": " . $p . " no es " . $end_string);
                        }
                    }

                }

                break;

        }
    }

    private function validateCategories(&$validate, $categories)
    {
        if (is_null($categories)) {
            $validate->setError(422, CM::FIELD . ' categories required');
        }
    }

    private function validateQuantity($id, $active, $validate, $clientId)
    {
        //instancio el servicio
        $vendeConfigPlan = new VendeConfigPlanService();
        $configVende = $vendeConfigPlan->validatePlan($clientId);
        if (!$configVende) {
            //el codigo 100002 es para identificar el error del plan no activo ni renovado al el cliente (dashboard)
            return $validate->setError(10002, CM::PLAN_CANCEL);
        }

        $catalogs = $vendeConfigPlan->getTotalActiveCatalogsV2($clientId, CM::ORIGIN_EPAYCO, null, true);
        $catalogs = $catalogs ? $catalogs : [];
        $totalProducts = $vendeConfigPlan->getTotalActiveProductsV2([], CM::ORIGIN_EPAYCO, null, $clientId);
        $oldProduct = !$id ? null : $vendeConfigPlan->getTotalActiveProductsV2(array(), CM::ORIGIN_EPAYCO, $id);
        $totalProducts = $totalProducts ? count($totalProducts) : 0;
        //si el producto es nuevo y ya posee el limite de productos activos ó
        //si lo que desea es activar un producto anterior ya teniendo el limite de productos activos
        if (($id == 0 && $configVende['allowedProducts'] != 'ilimitado' && $configVende['allowedProducts'] <= $totalProducts) ||
            ($id !== 0 && $active && $configVende['allowedProducts'] != 'ilimitado' && $oldProduct && !$oldProduct[0]->activo && ($totalProducts >= $configVende['allowedProducts']))
        ) {
            //el codigo 100001 es para identificar el error por exceder los limites del plan en el cliente (dashboard)
            $validate->setError(10001, CM::PLAN_EXCEEDED);
        }
    }

    private function validateProductTitle($id, $title, $validate, $clientId)
    {

        $id = ($id == "") ? false : intval($id);


        $products = $this->productRepository->searchProductTitle($title, $clientId, $id);
        if ($products && count($products) > 0) {
            $validate->setError(422, "El nombre del producto ya existe");
        }
    }
}
