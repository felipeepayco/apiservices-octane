<?php
namespace App\Service\V2\Catalogue\Process;

use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ProductRepository;
use App\Helpers\Validation\ValidateUrlImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShowConfigurationCatalogueService extends HelperPago
{

    private $catalogue_repository;
    private $product_repository;

    public function __construct(
        Request $request,
        CatalogueRepository $catalogue_repository,
        ProductRepository $product_repository,
    ) {
        parent::__construct($request);

        $this->catalogue_repository = $catalogue_repository;
        $this->product_repository = $product_repository;
    }

    public function handle($params)
    {
        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $origin = (string) CommonValidation::validateIsSet((array) $filters, 'origin', "", 'string');
            $manage = CommonValidation::validateIsSet((array) $filters, 'manage', 0, 'int');
            $catalogueId = (int) CommonValidation::validateIsSet((array) $filters, "catalogueId", "", "string");
            $catalogueName = (string) CommonValidation::validateIsSet((array) $filters, "catalogueName", "", "string");
            $onlyWithProducts = CommonValidation::validateIsSet((array) $filters, "onlyWithProducts", false, "bool");
            $countProducts = CommonValidation::validateIsSet((array) $filters, "countProducts", false, "bool");
            $onlyActive = CommonValidation::validateIsSet((array) $filters, 'onlyActive', false, "bool");
            $apifyClient = $this->getAlliedEntity($clientId);

            $vendeConfig = new VendeConfigPlanService();

            $planActive = $vendeConfig->getClientActivePlanBbl($clientId);
            $isRenovationDateExpired= $this->validateSubscriptonRenovationDate($planActive);
            $redis = [];
            $redis = app('redis')->connection();
            $exist = $redis->exists('vende_catalogue_' . $catalogueId);

            if ($exist && $planActive !== null && $manage === 0 && !$isRenovationDateExpired && false) {
                
                //THIS CONDITION IS NEVER EXECUTED

                $value = $redis->get('vende_catalogue_' . $catalogueId);
                $dataRedis = json_decode($value, true);
                $catalogueResponse = collect($dataRedis['catalogue'])->toArray();
                $categories = collect($dataRedis['categories'])->toArray();
                $productsOutstanding = collect($dataRedis['productsOutstanding'])->toArray();
            } else if ($planActive !== null &&  !$isRenovationDateExpired) {

                $paramsPlus = [
                    "onlyWithProducts" => $onlyWithProducts,
                    "countProducts" => $countProducts,
                    "onlyActive" => $onlyActive,
                ];

                list($catalogueResponse, $categories, $productsOutstanding) = $this->handleLoadConfig($origin, $catalogueName, $apifyClient, $clientId, $catalogueId, $redis, $paramsPlus, $manage);
            }


            $success = true;
            $title_response = $planActive !== null ? 'Successful consult data landing' : 'plan inactivo';
            $text_response = 'successful consult data landing';
            $last_action = 'successful consult data landing';

            $data = [
                "catalogue" => $planActive !== null ? $catalogueResponse : [],
                "categories" => $planActive !== null ? $categories : [],
                "productsOutstanding" => $planActive !== null ? $productsOutstanding : [],
            ];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error query to database ";
            $last_action = 'fetch data from database';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }


    private function validateSubscriptonRenovationDate($plan)
    {
        if ($plan) {
            $days = 3;
    
            $currentDate = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'))->setTime(0, 0, 0);
            $expireDate = Carbon::createFromFormat('Y-m-d', Carbon::parse($plan->fecha_renovacion)->format('Y-m-d'))->setTime(0, 0, 0);
    
            $addedDays = Carbon::parse($expireDate)->addDays($days)->format('Y-m-d');
            $addedDays = Carbon::createFromFormat('Y-m-d', $addedDays)->setTime(0, 0, 0);
    
            return $currentDate->greaterThanOrEqualTo($addedDays);
        }
        return true;
   

    }

    private function handleLoadConfig($origin, $catalogueName, $apifyClient, $clientId, $catalogueId, $redis, $paramsPlus, $manage)
    {
        $onlyWithProducts = $paramsPlus['onlyWithProducts'];
        $countProducts = $paramsPlus['countProducts'];
        $onlyActive = $paramsPlus['onlyActive'];
        $origin = "epayco";


        list($categories, $catalogue) = $this->loadCategories($clientId, $origin, $catalogueId, $catalogueName, $onlyWithProducts, $countProducts, $onlyActive, $manage);
        $catalogueResponse = null;

        if ($catalogue !== null) {

            $criteria = [
                'catalogo_id' => $catalogueId,
                'estado' => 1,
            ];

            if ($manage == 0) {
                $criteria['activo'] = true;
            }
            $products = $this->product_repository->getByCriteria($criteria);
            $catalogueSolds = $products->sum('ventas');
            $catalogueProductsCount = $products->sum('disponible');
            $catalogueResponse = [
                "date" => CommonValidation::validateIsSet((array) $catalogue, 'fecha', "", "date"),
                "updateDate" => CommonValidation::validateIsSet((array) $catalogue, 'fecha_actualizacion', "", "date"),
                "name" => CommonValidation::getFieldValidation((array) $catalogue, 'nombre'),
                "image" => (isset($catalogue["imagen"]) && $catalogue["imagen"] != "") ? ValidateUrlImage::locateImage($catalogue["imagen"])  : "",
                "id" => $catalogueId,
                "availableproducts" => $catalogueProductsCount,
                "soldproducts" => $catalogueSolds,
                "edataStatus" => CommonValidation::getFieldValidation((array) $catalogue, 'edata_estado', HelperEdata::STATUS_ALLOW),
            ];

            $this->addStatusCatalogue($catalogue, $origin, $catalogueResponse);

            $this->setEpaycoCatalogueResponseData($catalogue, $catalogueResponse, $origin);

        }

        $productsOutstanding = $this->loadProductsOutstanding($clientId, $origin, $catalogueId, $catalogue);
        $dataCache = [
            "catalogue" => $catalogueResponse,
            "categories" => $categories,
            "productsOutstanding" => $productsOutstanding,
        ];

        
        if ($manage === 0) {
            $redis->set('vende_catalogue_' . $catalogueId, json_encode($dataCache));
        }
        return array($catalogueResponse, $categories, $productsOutstanding);
    }

    private function loadCategories($clientId, $origin, $catalogueId, $catalogueName, $onlyWithProducts, $countProducts, $onlyActive, $manage)
    {

        $catalogues = $this->catalogue_repository->getCategories($clientId, $catalogueId, $catalogueName, $origin);
        $catalogue = $catalogues->first()->toArray();
        $catalogueName = "";
        $categories = $this->buildCategoriesDataResponse(
            $catalogues,
            $onlyWithProducts,
            $origin,
            $catalogueName,
            $countProducts,
            $onlyActive,
            $clientId,
            $manage
        );

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            usort($categories, function ($item1, $item2) {
                return $item1['date'] < $item2['date'];
            });
        } else {
            usort($categories, function ($item1, $item2) {
                return strtolower($item1['name']) > strtolower($item2['name']);
            });
        }
        $categories = array_values(array_filter($categories, function ($item) {
            return !($item["id"] === 1 && $item["name"] === "General");
        }));

        return [$categories, $catalogue];
    }

    private function buildCategoriesDataResponse($searchCategoryExistResult, $onlyWithProducts, $origin, &$catalogueName, $countProducts, $onlyActive, $clientId, $manage)
    {
        $categories = [];
        foreach ($searchCategoryExistResult as $catalogueResult) {

            $catalogueName = $catalogueResult["nombre"];
            $updateDate = CommonValidation::getFieldValidation((array) $catalogueResult, 'fecha_actualizacion', $catalogueResult["fecha"]);
            $categoriesHits = $catalogueResult["categorias"];
            $categoryCatalogueId = $catalogueResult["id"];

            $categoriesInCatalogue = $this->getCategoriesInCatalogue($categoriesHits);
            foreach ($categoriesHits as $categoryHits) {
                $categorySource = $categoryHits;
                $categoryId = $categorySource["id"];
                $newCategory = [
                    "id" => $categoryId,
                    "name" => $categorySource["nombre"],
                    "date" => date("Y-m-d H:i:s", strtotime($categorySource["fecha"])),
                    "catalogueId" => $categoryCatalogueId,
                    "edataStatus" => CommonValidation::getFieldValidation((array) $categorySource, 'edata_estado', HelperEdata::STATUS_ALLOW),
                ];

                $this->setEpaycoCategoryData($categorySource, $origin, $newCategory, $catalogueName, $updateDate, $categoriesInCatalogue);

                if ($onlyWithProducts || $countProducts) {

                    $products = $this->product_repository->getByCategories($categoryId, 1);
                    if (count($products)) {
                        $productsInCategoryResult = $products->sortBy(function ($product) {
                            return $product["precio_descuento"] == 0 ? $product["valor"] : $product["precio_descuento"];
                        });

                        $this->addProductCount($origin, $countProducts, $productsInCategoryResult, $newCategory);

                        if ($this->isValid($origin, $onlyActive, $newCategory, $productsInCategoryResult, $onlyWithProducts, $manage)) {

                            $newCategory["products"] = $this->formatProducts($productsInCategoryResult, $origin, $catalogueName, $categorySource["nombre"], $clientId);
                            array_push($categories, $newCategory);

                        }
                    }

                } else {
                    array_push($categories, $newCategory);
                }

            }
        }
        return $categories;

    }

    private function getCategoriesInCatalogue($categories)
    {
        $countCategories = 0;
        foreach ($categories as $categoryHits) {
            $categorySource = $categoryHits;
            if ($categorySource["estado"]) {
                $countCategories++;
            }
        }

        return $countCategories;
    }

    private function setEpaycoCategoryData($categorySource, $origin, &$data, $catalogueName, $updateDate, $categoriesInCatalogue)
    {

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $tempActive = true;
            if (isset($categorySource["activo"]) && !$categorySource["activo"]) {
                $tempActive = false;
            }

            $img = CommonValidation::getFieldValidation((array) $categorySource, 'img', '');
            $data["logo"] = $img != "" ? ValidateUrlImage::locateImage($img) : "";
            $data["catalogueName"] = $catalogueName;
            $data["origin"] = CommonText::ORIGIN_EPAYCO;
            $data[CommonText::ACTIVE_ENG] = CommonValidation::getFieldValidation((array) $categorySource, 'activo', $tempActive);
            $data["statusCategory"] = $this->getCataegoryStatus($data);
            $data["categoriesInCatalogue"] = $categoriesInCatalogue;
            $data["updateDate"] = date("Y-m-d H:i:s", strtotime($updateDate));
        }
    }

    private function getCataegoryStatus($categoryData)
    {

        $status = $categoryData[CommonText::ACTIVE_ENG] ? "Activo" : "Inactivo";

        if ($categoryData["edataStatus"] == HelperEdata::STATUS_ALERT) {
            $status = HelperEdata::STATUS_ALERT;
        }

        return $status;
    }

    private function addProductCount($origin, $countProducts, $productsInCategory, &$newCategory)
    {
        $productsActiveInCategory = 0;
        foreach ($productsInCategory as $product) {
            if (isset($product["activo"]) && $product["activo"]) {
                $productsActiveInCategory = $productsActiveInCategory + 1;
            }
        }
        if ($origin == CommonText::ORIGIN_EPAYCO && $countProducts) {
            $newCategory["productsInCategory"] = count($productsInCategory);
            $newCategory["productsActiveInCategory"] = $productsActiveInCategory;
        }
    }

    private function isValid($origin, $onlyActive, $category, $productsInCategoryResult, $onlyWithProducts, $manage = 0)
    {
        $isValid = true;

        if ($origin == CommonText::ORIGIN_EPAYCO && $onlyActive && ((!$category[CommonText::ACTIVE_ENG] && $manage === 0) || (!$category[CommonText::ACTIVE_ENG] && $manage === 1 && $category["edataStatus"] === "Permitido"))) {
            $isValid = false;
        }

        if ($onlyWithProducts && empty($productsInCategoryResult)) {
            $isValid = false;
        }
        return $isValid;
    }

    private function formatProducts($productsData, $origin, $catalogueName, $categoryName, $clientId, $catalogue = null)
    {
        $data = [];
        foreach ($productsData as $key => $value) {
            if ($origin == CommonText::ORIGIN_EPAYCO) {
                $data[$key]['showInventory'] = CommonValidation::getFieldValidation((array) $value, 'mostrar_inventario', false);
                $data[$key]['discountRate'] = $value["porcentaje_descuento"];
                $data[$key]['updateDate'] = CommonValidation::getFieldValidation((array) $value, 'fecha_actualizacion', $value["fecha"]);
                $data[$key]['outstanding'] = $value["destacado"];
                $data[$key]['discountPrice'] = $value["precio_descuento"];
                $data[$key]['origin'] = $value["origen"];
                $data[$key]['catalogueName'] = $catalogueName;
                $data[$key]['catalogueId'] = $value["catalogo_id"];
                $data[$key]['active'] = !isset($value["activo"]) ? true : $value["activo"];
                $data[$key]['statusProduct'] = $this->getProductStatus($data[$key]);
                $data[$key]['sales'] = $value["ventas"];
                $data[$key]['activeTax'] = CommonValidation::getFieldValidation((array) $value, 'iva_activo', false);
                $data[$key]['activeConsumptionTax'] = CommonValidation::getFieldValidation((array) $value, 'ipoconsumo_activo', false);
                $data[$key]['consumptionTax'] = CommonValidation::getFieldValidation((array) $value, 'ipoconsumo', 0);
                $data[$key]['netAmount'] = CommonValidation::getFieldValidation((array) $value, 'monto_neto', $value["valor"]);
                $data[$key]['salePrice'] = $value["porcentaje_descuento"] > 0 ? $value["precio_descuento"] : $value["valor"];
                $data[$key]['epaycoDeliveryProvider'] = CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_LOGISTIC, false);
                $data[$key]['epaycoDeliveryProviderValues'] = CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES, []);
                $data[$key]['realWeight'] = CommonValidation::getFieldValidation((array) $value, CommonText::REAL_WEIGHT, 0);
                $data[$key]['high'] = CommonValidation::getFieldValidation((array) $value, CommonText::HIGH, 0);
                $data[$key]['long'] = CommonValidation::getFieldValidation((array) $value, CommonText::LONG, 0);
                $data[$key]['width'] = CommonValidation::getFieldValidation((array) $value, CommonText::WIDTH, 0);
                $data[$key]['declaredValue'] = CommonValidation::getFieldValidation((array) $value, CommonText::DECLARED_VALUE, 0);

                if ($categoryName !== "") {
                    $data[$key]['categoryName'] = $categoryName;
                    $data[$key]['statusCategory'] = "Activo";
                } else {
                    $categoryName2 = "";
                    $statusCategory = "";
                    $this->setCategoryNameAndStatus($categoryName2, $statusCategory, $catalogue, $value);
                    $data[$key]['categoryName'] = $categoryName2;
                    $data[$key]['statusCategory'] = $statusCategory;
                }
            }

            $data[$key]['date'] = $value["fecha"];
            $data[$key]['state'] = $value["estado"];
            $data[$key]['txtCode'] = $value["id"];
            $data[$key]['clientId'] = $clientId;
            $data[$key]['quantity'] = $value["cantidad"];
            $data[$key]['baseTax'] = $value["base_iva"];
            $data[$key]['description'] = $value["descripcion"];
            $data[$key]['title'] = $value["titulo"];
            $data[$key]['currency'] = $value["moneda"];
            $data[$key]['urlConfirmation'] = $value["url_confirmacion"];
            $data[$key]['urlResponse'] = $value["url_respuesta"];
            $data[$key]['tax'] = $value["iva"];
            $data[$key]['amount'] = $value["valor"];
            $data[$key]['invoiceNumber'] = $value["numerofactura"];
            $data[$key]['expirationDate'] = $value["fecha_expiracion"];
            $data[$key]['contactName'] = $value["nombre_contacto"];
            $data[$key]['contactNumber'] = $value["numero_contacto"];
            $data[$key]['id'] = $value["id"];
            $data[$key]['lastMonthSales'] = CommonValidation::getFieldValidation((array) $value, 'ventas_ultimo_mes', 0);
            $data[$key]['edataStatus'] = CommonValidation::getFieldValidation((array) $value, 'edata_estado', HelperEdata::STATUS_ALLOW);

            $this->formatProductsFieldImg($data, $key, $value, $origin);
            $this->formatProductsFieldShippingTypesAndCategories($data, $key, $value);
            $this->formatProductsFiledReference($data, $key, $value);
        }
        $data = array_values($data);

        return $data;
    }

    private function getProductStatus($product)
    {
        $status = $product['active'] === true ? "Activo" : "Inactivo";
        return $status;
    }

    private function formatProductsFieldImg(&$data, $key, $value, $origin)
    {
        if (isset($value["img"])) {
            $data[$key]['img'] = [];
            foreach ($value["img"] as $ki => $img) {
                if (!empty($img)) {
                    $data[$key]['img'][$ki] = ValidateUrlImage::locateImage($img);
                }
            }
        } else {
            $data[$key]['img'] = [];
        }

        if ($origin) {
            $data[$key]['firsImage'] = isset($data[$key]['img'][0]) ? $data[$key]['img'][0] : "";
        }
    }

    private function formatProductsFieldShippingTypesAndCategories(&$data, $key, $value)
    {

        $data[$key]['shippingTypes'] = [];

        if (isset($value["envio"]) && count($value["envio"]) > 0) {
            foreach ($value["envio"] as $kv => $env) {
                $data[$key]['shippingTypes'][$kv]['type'] = $env["tipo"];
                $data[$key]['shippingTypes'][$kv]['amount'] = $env["valor"];
            }
        }

        if (isset($value["categorias"]) && count($value["categorias"]) > 0) {
            foreach ($value["categorias"] as $kc => $cat) {
                $data[$key]['categories'][$kc] = $cat;
            }
        }
    }

    private function formatProductsFiledReference(&$data, $key, $value)
    {
        $data[$key]['references'] = [];
        if (isset($value["referencias"]) && count($value["referencias"]) > 0 && $value["referencias"][0]["id"] != null) {
            $available = 0;
            foreach ($value["referencias"] as $kref => $ref) {
                $data[$key]['references'][$kref]['description'] = CommonValidation::getFieldValidation((array) $ref, 'descripcion', '');
                $data[$key]['references'][$kref]['invoiceNumber'] = CommonValidation::getFieldValidation((array) $ref, 'numerofactura', '');
                $data[$key]['references'][$kref]['urlResponse'] = CommonValidation::getFieldValidation((array) $ref, 'url_respuesta', '');
                $data[$key]['references'][$kref]['amount'] = CommonValidation::getFieldValidation((array) $ref, 'valor', 0);
                $data[$key]['references'][$kref]['expirationDate'] = CommonValidation::getFieldValidation((array) $ref, 'fecha_expiracion', '');
                $data[$key]['references'][$kref]['title'] = CommonValidation::getFieldValidation((array) $ref, 'nombre', '');
                $data[$key]['references'][$kref]['baseTax'] = CommonValidation::getFieldValidation((array) $ref, 'base_iva', 0);
                $data[$key]['references'][$kref]['date'] = CommonValidation::getFieldValidation((array) $ref, 'fecha', '');
                $data[$key]['references'][$kref]['urlConfirmation'] = CommonValidation::getFieldValidation((array) $ref, 'url_confirmacion', '');
                $data[$key]['references'][$kref]['txtCode'] = CommonValidation::getFieldValidation((array) $ref, 'txtcodigo', '');
                $data[$key]['references'][$kref]['tax'] = CommonValidation::getFieldValidation((array) $ref, 'iva', 0);
                $data[$key]['references'][$kref]['currency'] = CommonValidation::getFieldValidation((array) $ref, 'moneda', '');
                $data[$key]['references'][$kref]['quantity'] = CommonValidation::getFieldValidation((array) $ref, 'cantidad', 0);
                $data[$key]['references'][$kref]['id'] = CommonValidation::getFieldValidation((array) $ref, 'id', '');
                $data[$key]['references'][$kref]['available'] = CommonValidation::getFieldValidation((array) $ref, 'disponible', 0);
                $data[$key]['references'][$kref]['discountRate'] = CommonValidation::getFieldValidation((array) $ref, 'porcentaje_descuento', 0);
                $data[$key]['references'][$kref]['discountPrice'] = CommonValidation::getFieldValidation((array) $ref, 'precio_descuento', 0);
                $data[$key]['references'][$kref]['netAmount'] = CommonValidation::getFieldValidation((array) $ref, 'monto_neto', 0);
                $data[$key]['references'][$kref]['consumptionTax'] = CommonValidation::getFieldValidation((array) $ref, 'ipoconsumo', 0);
                $available = $available + $ref["disponible"];
                $data[$key]['references'][$kref]['img'] = $this->formatProductsFiledReferenceHelper($ref);
                $data[$key]['available'] = $available;
            }
        } else {
            $data[$key]['available'] = $value["disponible"];
        }
    }

    private function formatProductsFiledReferenceHelper($ref)
    {
        $img = [];
        if (isset($ref["img"]) && is_array(($ref["img"]))) {
            $referencesImg = [];
            foreach ($ref["img"] as $referenceImg) {
                array_push($referencesImg, ValidateUrlImage::locateImage($referenceImg));
            }
            $img = $referencesImg;
        } else if (isset($ref["img"])) {
            $img = $ref["img"] !== '' && $ref["img"] !== null ? ValidateUrlImage::locateImage($ref["img"]) : null;
        }
        return $img;
    }

    private function loadProductsOutstanding($clientId, $origin, $catalogueId, $catalogue)
    {

        $productsOutstanding = $this->product_repository->getOutstandingProducts($catalogueId, 1, true, $origin);

        $sortedProducts = $productsOutstanding->sortBy(function ($product) {
            return $product["precio_descuento"] == 0 ? $product["valor"] : $product["precio_descuento"];
        });

        $productsOutstandingResult = $sortedProducts->toArray();
        return $this->formatProducts($productsOutstandingResult, $origin, $catalogue["nombre"], "", $clientId, $catalogue);

    }

    private function setCategoryNameAndStatus(&$categoryName, &$statusCategory, $catalogue, $value)
    {

        if (isset($value["categorias"]) && !empty($value["categorias"])) {
            $categoryId = $value["categorias"][0];
            $categories = $catalogue["categorias"];
            $targetCategoryIndex = array_search($categoryId, array_column((array) $categories, 'id'));
            $targetCategory = $categories[$targetCategoryIndex];
            $categoryName = $targetCategory["nombre"];
            if ($targetCategory["id"] == 1 || (isset($targetCategory["activo"]) && !$targetCategory["activo"])) {
                $statusCategory = "Inactivo";
            }
        }

    }

    private function addStatusCatalogue($catalogue, $origin, &$catalogueResponseData)
    {
        if ($origin === 'epayco') {

            $isActive = CommonValidation::getFieldValidation($catalogue, 'activo', true);
            $status = 'en construcción';
            $progressCatalogue = CommonValidation::getFieldValidation($catalogue, 'progreso', $status);

            if ($isActive && $progressCatalogue === "publicado") {

                $status = "activo";
            } else if (!$isActive) {
                $status = "inactivo";
            }

            if (isset($catalogue["edata_estado"]) && $catalogue["edata_estado"] == HelperEdata::STATUS_ALERT) {
                $status = HelperEdata::STATUS_ALERT;
            }

            if (isset($catalogue["estado_plan"]) && $catalogue["estado_plan"] === 'suspendido') {
                $status = "suspendido";
            }
            $catalogueResponseData['statusCatalogue'] = $status;
        }
    }

    private function setEpaycoCatalogueResponseData($catalogue, &$catalogueResponseData, $origin)
    {

        if ($origin == "epayco") {
            $active = !is_bool(CommonValidation::getFieldValidation((array) $catalogue, "activo")) || CommonValidation::getFieldValidation((array) $catalogue, "activo") === false ? false : true;
            if (isset($catalogue["estado_plan"]) && $catalogue["estado_plan"] == "suspendido") {
                $active = false;
            }
            $catalogueResponseData["companyName"] = CommonValidation::getFieldValidation((array) $catalogue, "nombre_empresa");
            $catalogueResponseData["origin"] = CommonValidation::getFieldValidation((array) $catalogue, "procede");
            $catalogueResponseData["contactPhone"] = CommonValidation::getFieldValidation((array) $catalogue, "telefono_contacto");
            $catalogueResponseData["contactEmail"] = CommonValidation::getFieldValidation((array) $catalogue, "correo_contacto");
            $catalogueResponseData["whatsappActive"] = CommonValidation::getFieldValidation((array) $catalogue, "whatsapp_activo", false);
            $catalogueResponseData["color"] = CommonValidation::getFieldValidation((array) $catalogue, "color");
            $catalogueResponseData["progress"] = CommonValidation::getFieldValidation((array) $catalogue, "progreso");
            $catalogueResponseData["banners"] = $this->getBannersUrl($catalogue);
            $catalogueResponseData["active"] = $active;

            $catalogueResponseData["indicativoPais"] = CommonValidation::getFieldValidation((array) $catalogue, "indicativo_pais", "+57");
            $catalogueResponseData[CommonText::CURRENCY_ENG] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::CURRENCY, CommonText::COP_CURRENCY_CODE);
            $catalogueResponseData["default_language"] = CommonValidation::getFieldValidation((array) $catalogue, 'idioma', 'ESP');
            $catalogueResponseData["providerDelivery"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::PROVIDER_DELIVERY);
            $catalogueResponseData["epaycoDeliveryProvider"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::EPAYCO_LOGISTIC);
            $catalogueResponseData["senderType"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_TYPE);
            $catalogueResponseData["senderFirstname"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_FIRSTNAME);
            $catalogueResponseData["senderLastname"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_LASTNAME);
            $catalogueResponseData["senderDocType"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_DOC_TYPE);
            $catalogueResponseData["senderDoc"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_DOC);
            $catalogueResponseData["senderPhone"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_PHONE);
            $catalogueResponseData["senderBusiness"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::SENDER_BUSINESS);
            $catalogueResponseData["epaycoDeliveryProviderValues"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES);
            $catalogueResponseData["pickupCity"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::PICKUP_CITY);
            $catalogueResponseData["pickupDepartament"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::PICKUP_DEPARTAMENT);
            $catalogueResponseData["pickupAddress"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::PICKUP_ADDRESS);
            $catalogueResponseData["pickupConfigurationId"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::PICKUP_CONFIGURATION_ID);
            $catalogueResponseData["automaticPickup"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::AUTOMATIC_PICKUP);
            $catalogueResponseData["freeDelivery"] = CommonValidation::getFieldValidation((array) $catalogue, CommonText::FREE_DELIVERY);

            $totalProductActive = $this->product_repository->getTotalActiveProducts([$catalogue], CommonText::ORIGIN_EPAYCO);

            $catalogueResponseData["totalProductActive"] = count($totalProductActive);
            $analytics = CommonValidation::getFieldValidation((array) $catalogue, "analiticas", []);

            $catalogueResponseData["analytics"] = [
                "facebookPixelActive" => CommonValidation::getFieldValidation((array) $analytics, "facebook_pixel_active", false),
                "facebookPixelId" => CommonValidation::getFieldValidation((array) $analytics, "facebook_pixel_id", ""),
                "googleAnalyticsActive" => CommonValidation::getFieldValidation((array) $analytics, "google_analytics_active", false),
                "googleAnalyticsId" => CommonValidation::getFieldValidation((array) $analytics, "google_analytics_id", ""),
                "googleTagManagerActive" => CommonValidation::getFieldValidation((array) $analytics, "google_tag_manager_active", false),
                "googleTagManagerId" => CommonValidation::getFieldValidation((array) $analytics, "google_tag_manager_id", ""),
            ];
        }
    }

    private function getBannersUrl($catalogue)
    {

        $banners = CommonValidation::getFieldValidation((array) $catalogue, "banners", []);
        $bannersWithUrl = [];

        foreach ($banners as $banner) {
            $path = "";
            if ($banner != "") {
                $path = ValidateUrlImage::locateImage($banner);
            }
            array_push($bannersWithUrl, $path);
        }

        return $bannersWithUrl;
    }
}
