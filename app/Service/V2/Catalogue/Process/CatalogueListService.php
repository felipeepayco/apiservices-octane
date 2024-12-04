<?php

namespace App\Service\V2\Catalogue\Process;

use App\Helpers\Messages\CommonText as CM;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\ValidateUrlImage;
use App\Listeners\Services\VendeConfigPlanService;
use App\Models\BblClientes;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\ProductRepository;

class CatalogueListService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected ClientRepository $clientRepository;

    public function __construct(CatalogueRepository $catalogueRepository, ProductRepository $productRepository, ClientRepository $clientRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->clientRepository = $clientRepository;
    }
    public function process($data)
    {

        try {
            $fieldValidation = $data;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $pagination = $fieldValidation["pagination"];

            $id = CommonValidation::getFieldValidation((array) $filters, 'id');
            $name = CommonValidation::getFieldValidation((array) $filters, 'name');
            $strictName = CommonValidation::getFieldValidation((array) $filters, 'strictName');
            $initialDate = CommonValidation::getFieldValidation((array) $filters, 'initialDate');
            $endDate = CommonValidation::getFieldValidation((array) $filters, 'endDate');
            $origin = "epayco";
            $progress = CommonValidation::getFieldValidation((array) $filters, "progress");
            $notFound = CommonValidation::getFieldValidation((array) $filters, "notFound");
            $searchValue = CommonValidation::getFieldValidation((array) $filters, 'search');

            $page = CommonValidation::getFieldValidation((array) $pagination, 'page', 1);
            $pageSize = CommonValidation::getFieldValidation((array) $pagination, 'limit', 50);

            $query = [];

            $query['cliente_id'] = $clientId;

            if (!$notFound) {
                $query['estado'] = true;
            }

            if (!empty($id)) {
                $query['id'] = $id;
            }

            if (!empty($name)) {
                $nameQuery = $strictName ? $name : ['$regex' => $name, '$options' => 'i'];
                $query['nombre'] = $nameQuery;
            }

            $this->setRangeDateSearch($initialDate, $endDate, $query);
            $this->setEpaycoSearch($progress, $query);
            //$this->filterSortCatalogueEpayco($query, $strictName);

            $catalogueResult = $this->catalogueRepository->listCatalogueParameterized($query, $pageSize, $page);

            $catalogueList = [];

            $dataUser = $this->clientRepository->find($clientId);

            foreach ($catalogueResult as $catalogue) {

                $catalogueId = $catalogue->id;

                $catalogueSolds = $this->productRepository->getTotalSoldByCatalogue($catalogue->id) ?? 0;
                $catalogueProductsCount = $this->productRepository->getTotalAvaliableByCatalogue($catalogue->id) ?? 0;

                $catalogueArray = $catalogue->toArray();
                $catalogueResponseData = [
                    "date" => $this->getDateValidation($catalogueArray, 'fecha'),
                    "updateDate" => $this->getDateValidation($catalogueArray, 'fecha_actualizacion'),
                    "name" => CommonValidation::getFieldValidation($catalogueArray, 'nombre'),
                    "image" => (isset($catalogue->imagen) && $catalogue->imagen != "") ? ValidateUrlImage::locateImage($catalogue->imagen) : "",
                    "id" => $catalogueId,
                    "availableproducts" => $catalogueProductsCount,
                    "soldproducts" => $catalogueSolds,
                    "cname" => $dataUser->cname,
                    "edataStatus" => $this->validateEnabledClient($origin, $catalogue, $clientId)
                ];

                $this->addStatusCatalogue($catalogueArray, $catalogueResponseData);
                $this->setEpaycoCatalogueResponseData($catalogueArray, $catalogueResponseData);

                array_push($catalogueList, $catalogueResponseData);
            }
            $totalCount = count($catalogueResult);
            $responseData = [
                "data" => $catalogueList,
                "current_page" => $page,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "last_page" => ceil($totalCount / $pageSize),
                "next_page_url" => "/catalogue?page=" . ($page + 1),
                "path" => $this->getClientSubdomain($clientId, $origin),
                "per_page" => $pageSize,
                "prev_page_url" => $page <= 1 ? null : "/catalogue?pague=" . ($page - 1),
                "to" => $page <= 1 ? count($catalogueList) : ($page * $pageSize) - ($pageSize - 1) + (count($catalogueList) - 1),
                "total" => $totalCount,
            ];

            $success = true;
            $title_response = 'Successfully consult list catalogue';
            $text_response = 'Successfully consult list catalogue';
            $last_action = 'consult_list_catalogue';
            $data = $responseData;
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error query to database";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' =>
                $validate->errorMessage,
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function validateEnabledClient($origin, $catalogue, $clientId)
    {
        $edataStatus = CommonValidation::getFieldValidation((array) $catalogue, 'edata_estado', "Permitido");

        return $edataStatus;
    }

    private function addStatusCatalogue($catalogue, &$catalogueResponseData)
    {

        $isActive = isset($catalogue['activo']) ? $catalogue['activo'] : true;
        $status = 'en construcción';
        $progressCatalogue = CommonValidation::getFieldValidation($catalogue, 'progreso', $status);

        if ($isActive && $progressCatalogue === "publicado") {
            $status = "activo";
        } else if (!$isActive) {
            $status = "inactivo";
        }

        if (isset($catalogue['estado_plan']) && $catalogue['estado_plan'] === 'suspendido') {
            $status = "suspendido";
        }

        $catalogueResponseData['statusCatalogue'] = $status;
    }

    private function filterSortCatalogueEpayco(&$query, $strictName = false)
    {

        $sort = [];
        if ($strictName) {
            $sort['id'] = -1; // DESC
        }
        $sort['fecha'] = -1; // DESC
        $query['sort'] = $sort;
    }

    private function getClientSubdomain($clientId, $origin)
    {
        $clientSubdomainSearch = BblClientes::find($clientId);

        $path = '';
        isset($clientSubdomainSearch->url) && $path = $clientSubdomainSearch->url;

        $origin !== 'epayco' ? $path = $path . "/catalogo/" : $path = $path . "/vende/";

        return $path;
    }

    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;
    }

    private function getDateValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? date("Y-m-d H:i:s", strtotime($fields[$name])) : $default;
    }

    private function setRangeDateSearch($initialDate, $endDate, &$query)
    {
        if ($initialDate != "" && $endDate != "") {
            $query['fecha'] = [
                '$gt' => $initialDate,
                '$lt' => $endDate,
            ];
        } elseif ($initialDate != "") {
            $query['fecha'] = [
                '$gt' => $initialDate,
            ];
        } elseif ($endDate != "") {
            $query['fecha'] = [
                '$lt' => $endDate,
            ];
        }
    }

    private function setEpaycoSearch($progress, &$query)
    {
        $query['procede'] = 'epayco';

        if ($progress == "building") {
            $query['$or'] = [
                ['progreso' => 'procesando'],
                ['progreso' => 'completado'],
            ];
        } else {
            if ($progress == "processing") {
                $query['progreso'] = 'procesando';
            } elseif ($progress == "completed") {
                $query['progreso'] = 'completado';
            } elseif ($progress == "published") {
                $query['progreso'] = 'publicado';
            }
        }
    }

    private function setEpaycoCatalogueResponseData($catalogue, &$catalogueResponseData)
    {

        $catalogueResponseData["companyName"] = CommonValidation::getFieldValidation($catalogue, "nombre_empresa");
        $catalogueResponseData["origin"] = CommonValidation::getFieldValidation($catalogue, "procede");
        $catalogueResponseData["contactPhone"] = CommonValidation::getFieldValidation($catalogue, "telefono_contacto");
        $catalogueResponseData["contactEmail"] = CommonValidation::getFieldValidation($catalogue, "correo_contacto");
        $catalogueResponseData["whatsappActive"] = CommonValidation::getFieldValidation($catalogue, "whatsapp_activo", false);
        $catalogueResponseData["color"] = CommonValidation::getFieldValidation($catalogue, "color");
        $catalogueResponseData["progress"] = CommonValidation::getFieldValidation($catalogue, "progreso");
        $catalogueResponseData["banners"] = $this->getBannersUrl($catalogue);
        $catalogueResponseData["active"] = $this->getCatalogueIsActive($catalogue);
        $catalogueResponseData["indicativoPais"] = CommonValidation::getFieldValidation($catalogue, "indicativo_pais", "+57");
        $catalogueResponseData[CM::CURRENCY_ENG] = CommonValidation::getFieldValidation($catalogue, CM::CURRENCY, CM::COP_CURRENCY_CODE);
        $catalogueResponseData["language"] = CommonValidation::getFieldValidation($catalogue, "idioma");

        $catalogueResponseData["providerDelivery"] = CommonValidation::getFieldValidation($catalogue, CM::PROVIDER_DELIVERY);
        $catalogueResponseData["epaycoDeliveryProvider"] = CommonValidation::getFieldValidation($catalogue, CM::EPAYCO_LOGISTIC);
        $catalogueResponseData["senderType"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_TYPE);
        $catalogueResponseData["senderFirstname"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_FIRSTNAME);
        $catalogueResponseData["senderLastname"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_LASTNAME);
        $catalogueResponseData["senderDocType"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_DOC_TYPE);
        $catalogueResponseData["senderDoc"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_DOC);
        $catalogueResponseData["senderPhone"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_PHONE);
        $catalogueResponseData["senderBusiness"] = CommonValidation::getFieldValidation($catalogue, CM::SENDER_BUSINESS);
        $catalogueResponseData["epaycoDeliveryProviderValues"] = CommonValidation::getFieldValidation($catalogue, CM::EPAYCO_DELIVERY_PROVIDER_VALUES);
        $catalogueResponseData["pickupCity"] = CommonValidation::getFieldValidation($catalogue, CM::PICKUP_CITY);
        $catalogueResponseData["pickupDepartament"] = CommonValidation::getFieldValidation($catalogue, CM::PICKUP_DEPARTAMENT);
        $catalogueResponseData["pickupAddress"] = CommonValidation::getFieldValidation($catalogue, CM::PICKUP_ADDRESS);
        $catalogueResponseData["pickupConfigurationId"] = CommonValidation::getFieldValidation($catalogue, CM::PICKUP_CONFIGURATION_ID);
        $catalogueResponseData["automaticPickup"] = CommonValidation::getFieldValidation($catalogue, CM::AUTOMATIC_PICKUP);
        $catalogueResponseData["freeDelivery"] = CommonValidation::getFieldValidation($catalogue, CM::FREE_DELIVERY);

        //SUBDOMAIN
        $catalogueResponseData["ownDomain"] = CommonValidation::getFieldValidation($catalogue, "dominio_propio");
        $catalogueResponseData["ownDomainValue"] = CommonValidation::getFieldValidation($catalogue, "valor_dominio_propio");
        $catalogueResponseData["ownSubDomainValue"] = CommonValidation::getFieldValidation($catalogue, "valor_subdominio_propio");
        //Si deleteOwnDomainValue es true el dominio se encuentra inactivo
        $catalogueResponseData["deleteOwnDomainValue"] = CommonValidation::getFieldValidation($catalogue, CM::DELETE_OWNDOMAINVALUE);
        //Si deleteOwnSubDomainValue es true el subDominio se encuentra inactivo
        $catalogueResponseData["deleteOwnSubDomainValue"] = CommonValidation::getFieldValidation($catalogue, CM::DELETE_OWNSUBDOMAINVALUE);

        $vendeConfigPlan = new VendeConfigPlanService();
        $totalProductActive = $vendeConfigPlan->getTotalActiveProductsV2([(object) $catalogue], CM::ORIGIN_EPAYCO);
        $catalogueResponseData["totalProductActive"] = count($totalProductActive);
        $catalogueResponseData["analytics"] = $this->getAnalyticsResponseParams(CommonValidation::getFieldValidation($catalogue, "analiticas"));

        $catalogueResponseData["nextAttempt"] = CommonValidation::getFieldValidation($catalogue, CM::NEXT_ATTEMPT, null);
        $catalogueResponseData["intentsCertification"] = CommonValidation::getFieldValidation($catalogue, CM::INTENTS_CERTIFICATION, null);
        $catalogueResponseData["possessesCertificate"] = CommonValidation::getFieldValidation($catalogue, CM::POSSESSES_CERTIFICATE, null);

    }
    private function getAnalyticsResponseParams($analytics)
    {

        return [
            "facebookPixelActive" => CommonValidation::getFieldValidation((array) $analytics, "facebook_pixel_active", false),
            "facebookPixelId" => CommonValidation::getFieldValidation((array) $analytics, "facebook_pixel_id", ""),
            "googleAnalyticsActive" => CommonValidation::getFieldValidation((array) $analytics, "google_analytics_active", false),
            "googleAnalyticsId" => CommonValidation::getFieldValidation((array) $analytics, "google_analytics_id", ""),
            "googleTagManagerActive" => CommonValidation::getFieldValidation((array) $analytics, "google_tag_manager_active", false),
            "googleTagManagerId" => CommonValidation::getFieldValidation((array) $analytics, "google_tag_manager_id", ""),
        ];
    }

    private function getCatalogueIsActive($catalogue)
    {
        $active = !is_bool(CommonValidation::getFieldValidation($catalogue, "activo")) || CommonValidation::getFieldValidation($catalogue, "activo") === false ? false : true;

        if (isset($catalogue["estado_plan"]) && $catalogue["estado_plan"] == "suspendido") {
            $active = false;
        }

        return $active;
    }

    private function getBannersUrl($catalogue)
    {
        $banners = CommonValidation::getFieldValidation($catalogue, "banners", []);
        $bannersWithUrl = [];

        foreach ($banners as $banner) {
            $path = "";
            if ($banner != "") {
                $path =  ValidateUrlImage::locateImage($banner);
            }
            array_push($bannersWithUrl, $path);
        }

        return $bannersWithUrl;
    }
}
