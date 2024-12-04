<?php
namespace App\Listeners\Vende\Process;


use App\Events\Vende\Process\ProcessConfigurationBabiloniaEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use App\Models\BblSuscripcion;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use App\Helpers\Validation\CommonValidation;

class ProcessConfigurationBabiloniaListener extends HelperPago
{

    const CLIENTE_ID = "cliente_id";
    const ID_CLIENTE = "id_cliente";
    const SUCCESS = "success";
    const EPAYCO = "epayco";
    const ESTADO = "estado";
    const ORIGIN = "origin";
    const PRODUCT_FREE_BABILONIA = 3;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ProcessConfigurationBabiloniaEvent $event)
    {
      try {
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation["clientId"];
        $catalogueId = isset($fieldValidation["id"]) ? $fieldValidation["id"] : 0;

        $client = BblClientes::find($clientId);
        if (!$client) {
          $success = false;
          $title_response = 'Error cliente babilonia no identificado';
          $text_response = "Error cliente ".$clientId;
          $last_action = 'fetch data from database ';
          $validate = new Validate();
          $validate->setError("403", "cliente babilonia no identificado");
          $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
              $validate->errorMessage);
        } else {

            $domain = $client->url;
            $totalCatalogs = $this->getCatalogueElastic($clientId, false, true); //todos sin importar estado
            $pendingCatalogs = $this->getCatalogues($totalCatalogs, ["procesando","completado"]); //solo los pendientes
            $catalogs = $this->getCatalogues($totalCatalogs, ["publicado"]); // solo los publicados
            $totalCategories = $this->getTotalCategories($totalCatalogs);
            list($pendingCatalogueId, $nameCatalogue, $pendingCatalogueInfoStatus, $countCategories, $companyName, $currency, $analytics, $providerDelivery, $epaycoDeliveryProvider, $epaycoDeliveryProviderValues, $senderType, $senderFirstname, $senderLastname, $senderDocType, $senderDoc, $senderPhone, $senderBusiness, $pickupCity, $pickupDepartament, $pickupAddress, $pickupConfigurationId, $automaticPickup, $freeDelivery) = $this->getFirstPendingCatalogueId($pendingCatalogs, $totalCatalogs, $catalogueId);
            $products = $this->getProductElastic($clientId, $pendingCatalogueId);
            $totalProducts = $this->getProductElastic($clientId, 0, true); //todos los productos no eliminados
            $productoTerminos =[];
            $checkoutTermsConditions = null;
       
            $tipoClient = null;

            $firstCatalogue = false;
            if (empty($catalogs['data'])) {
                $firstCatalogue = true;
            }

            $planArray = BblSuscripcion::checkPlanByDate($clientId,12, [1, 2, 5, 10], false, true);
            $plan = !empty($planArray) ? BblSuscripcion::formatResponsePlan($planArray[0]) : false;
            $totalCatalogsActive = $this->getTotalActive($catalogs);
            $totalProductsActive = $this->getTotalActive($totalProducts);

            $clientProductFree = $this->BBLSucriptionByPlanId($clientId, self::PRODUCT_FREE_BABILONIA);

            $pendingCatalogue = [
                "id" => $pendingCatalogueId,
                "name" => $nameCatalogue,
                "categories" => $countCategories,
                "products" => count($products['data']),
                "infoStatus" => $pendingCatalogueInfoStatus,
                "companyName" => $companyName,
                "currency" => $currency,
                "analytics" => $analytics,
                "providerDelivery" => $providerDelivery,
                "epaycoDeliveryProvider" => $epaycoDeliveryProvider,
                "epaycoDeliveryProviderValues" => $epaycoDeliveryProviderValues,
                "senderType" => $senderType,
                "senderFirstname" => $senderFirstname,
                "senderLastname" => $senderLastname,
                "senderDocType" => $senderDocType,
                "senderDoc" => $senderDoc,
                "senderPhone" => $senderPhone,
                "senderBusiness" => $senderBusiness,
                "pickupCity" => $pickupCity,
                "pickupDepartament" => $pickupDepartament,
                "pickupAddress" => $pickupAddress,
                "pickupConfigurationId" => $pickupConfigurationId,
                "automaticPickup" => $automaticPickup,
                "freeDelivery" => $freeDelivery,
            ];
    
            $data = [
                "dominio" => $domain ? $domain : "",
                "pendingCatalogue" => $pendingCatalogue,
                "firstCatalogue" => $firstCatalogue,
                "catalogs" => count($catalogs['data']),
                "totalCatalogs" => count($totalCatalogs['data']),
                "currency" => $this->getCatalogueCurrency($catalogs),
                "totalCategories" => $totalCategories,
                "totalProducts" => count($totalProducts['data']),
                "productoActivo" => $plan && $plan["estado"] === 1 ? true : false,
                "totalCatalogsActive" => $totalCatalogsActive ? count($totalCatalogsActive) : 0,
                "totalProductsActive" => $totalCatalogsActive ? count($totalProductsActive) : 0,
                "plan" => $plan,
                "permittedPlanFree" => $clientProductFree === null,
                "invoiceUrl" => "",
                "productoTerminos" => $productoTerminos,
                "checkoutTermsConditions" => $checkoutTermsConditions,
                "tipoClient" => $tipoClient,
            ];
            $success = true;
            $title_response = "babilonia_configuraciones";
            $text_response = "sucess load config";
            $last_action = "babilonia_configuraciones";
        }

      } catch (\Exception $exception) {
          $success = false;
          $title_response = 'Error '.$exception->getMessage();
          $text_response = "Error query to database ".$exception->getFile();
          $last_action = 'fetch data from database '.$exception->getLine();
          $error = $this->getErrorCheckout('E0100');
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

    private function getCatalogueElastic($clientId, $pending, $isTotal = false)
    {

        $search = new Search();
        $search->setSize(5000);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery(self::CLIENTE_ID, $clientId), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery(self::ESTADO, true), BoolQuery::FILTER);

        $search->addQuery(new MatchQuery('procede', self::EPAYCO), BoolQuery::FILTER);
        $search->addSort(new FieldSort('fecha', 'DESC'));

        if (!$isTotal) {
            $bool = new BoolQuery();
            if ($pending) {
                $bool->add(new TermQuery("progreso", "procesando"), BoolQuery::SHOULD);
                $bool->add(new TermQuery("progreso", "completado"), BoolQuery::SHOULD);
            }
            if (!$pending) {
                $bool->add(new TermQuery("progreso", "publicado"), BoolQuery::SHOULD);
            }
            $search->addQuery($bool, BoolQuery::FILTER);
        }

        $query = $search->toArray();
        return $this->consultElasticSearch($query, "catalogo", false);
    }
    
    private function getProductElastic($clientId, $pendingCatalogueId, $getTotalProduct = false)
    {
        $search = new Search();
        $search->setSize(5000);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery(self::CLIENTE_ID, $clientId), BoolQuery::FILTER);
        if (!$getTotalProduct) {
            $search->addQuery(new MatchQuery('catalogo_id', $pendingCatalogueId), BoolQuery::FILTER);
        }
        $search->addQuery(new MatchQuery(self::ESTADO, 1), BoolQuery::FILTER);
        $query = $search->toArray();
        return $this->consultElasticSearch($query, "producto", false);
    }

    public function getTotalActive($items)
    {
        return array_filter($items["data"], function ($item) {
            return !empty($item) && $item->activo;
        });
    }

    private function getTotalCategories($totalCatalogs)
    {
        $countTotalCategories = 0;
        $countCataloguetemp = 0;
        foreach ($totalCatalogs['data'] as $tempCatalogues) {
            $countCataloguetemp++;
            foreach ($tempCatalogues->categorias as $tempCategories) {
                if ($tempCategories->estado && $tempCategories->id > 1) {
                    $countTotalCategories++;
                }
            }
        }
        return $countTotalCategories;
    }

    private function getFirstPendingCatalogueId($pendingCatalogs, $catalogs, $findCatalogueId)
    {

        $catalogueId = 0;
        $nameCatalogue = "";
        $pendingCatalogueInfoStatus = "";
        $countCategories = 0;
        $companyName = "";
        $analytics = [
            "facebookPixelActive" => false,
            "facebookPixelId" => "",
            "googleAnalyticsActive" => false,
            "googleAnalyticsId" => "",
            "googleTagManagerActive" => false,
            "googleTagManagerId" => "",
        ];
        $currency = "";
        $providerDelivery = false;
        $epaycoDeliveryProvider = false;
        $epaycoDeliveryProviderValues = [];
        $senderType = "Persona";
        $senderFirstname = "";
        $senderLastname = "";
        $senderDocType = null;
        $senderDoc = "";
        $senderPhone = "";
        $senderBusiness = "";
        $pickupCity = "";
        $pickupDepartament = "";
        $pickupAddress = "";
        $pickupConfigurationId = 0;
        $automaticPickup = false;
        $freeDelivery = false;
        $pendingCatalogue = [];
        $redefineCatalogue = false;
        if ($findCatalogueId > 0) {
            $keyInCatalogs = array_search($findCatalogueId, array_column($catalogs["data"], 'id'));
            $pendingCatalogue = $catalogs["data"][$keyInCatalogs];
            $redefineCatalogue = true;
        }
        if (count($catalogs["data"]) == 1 && !empty($pendingCatalogs["data"])) {
            $pendingCatalogue = $pendingCatalogs["data"][0];
            $redefineCatalogue = true;
        }

        if ($redefineCatalogue && !empty($pendingCatalogue)) {
            $catalogueId = $pendingCatalogue->id;
            $nameCatalogue = $pendingCatalogue->nombre;
            $pendingCatalogueInfoStatus = $pendingCatalogue->progreso;
            $companyName = $pendingCatalogue->nombre_empresa;
            if (isset($pendingCatalogue->analiticas)) {
                $analytics = [
                    "facebookPixelActive" => $pendingCatalogue->analiticas->facebook_pixel_active,
                    "facebookPixelId" => $pendingCatalogue->analiticas->facebook_pixel_id,
                    "googleAnalyticsActive" => $pendingCatalogue->analiticas->google_analytics_active,
                    "googleAnalyticsId" => $pendingCatalogue->analiticas->google_analytics_id,
                    "googleTagManagerActive" => $pendingCatalogue->analiticas->google_tag_manager_active,
                    "googleTagManagerId" => $pendingCatalogue->analiticas->google_tag_manager_id,
                ];
            }
            $currency = CommonValidation::validateIsSet((array)$pendingCatalogue, "moneda", "COP" , "string");
            $providerDelivery = CommonValidation::validateIsSet((array)$pendingCatalogue, "proveedor_envios", false, "bool");
            $epaycoDeliveryProvider = CommonValidation::validateIsSet((array)$pendingCatalogue, "epayco_logistica", false, "bool");
            $epaycoDeliveryProviderValues = CommonValidation::validateIsSet((array)$pendingCatalogue, "lista_proveedores", [], "array");
            $senderType = CommonValidation::validateIsSet((array)$pendingCatalogue, "tipo_remitente", "Persona" , "string");
            $senderFirstname = CommonValidation::validateIsSet((array)$pendingCatalogue, "nombre_remitente", "" , "string");
            $senderLastname = CommonValidation::validateIsSet((array)$pendingCatalogue, "apellido_remitente", "" , "string");
            $senderDocType = CommonValidation::validateIsSet((array)$pendingCatalogue, "tipo_documento_remitente", null , "object");
            $senderDoc = CommonValidation::validateIsSet((array)$pendingCatalogue, "documento_remitente", "" , "string");
            $senderPhone = CommonValidation::validateIsSet((array)$pendingCatalogue, "telefono_remitente", "" , "string");
            $senderBusiness = CommonValidation::validateIsSet((array)$pendingCatalogue, "razon_social_remitente", "" , "string");
            $pickupCity = CommonValidation::validateIsSet((array)$pendingCatalogue, "ciudad_recogida", "" , "string");
            $pickupDepartament = CommonValidation::validateIsSet((array)$pendingCatalogue, "departamento_recogida", "" , "string");
            $pickupAddress = CommonValidation::validateIsSet((array)$pendingCatalogue, "direccion_recogida", "" , "string");
            $pickupConfigurationId = CommonValidation::validateIsSet((array)$pendingCatalogue, "configuracion_recogida_id", 0 , "number");
            $automaticPickup = CommonValidation::validateIsSet((array)$pendingCatalogue, "recogida_automatica", false, "bool");
            $freeDelivery = CommonValidation::validateIsSet((array)$pendingCatalogue, "envio_gratis", false, "bool");
            foreach ($pendingCatalogue->categorias as $item) {
                $value = (array) $item;
                if ($value[self::ESTADO] && $value['id'] > 1 && isset($value["activo"]) && $value["activo"]) {
                    $countCategories++;
                }
            }
        }

        return array($catalogueId, $nameCatalogue, $pendingCatalogueInfoStatus, $countCategories, $companyName, $currency, $analytics, $providerDelivery, $epaycoDeliveryProvider, $epaycoDeliveryProviderValues, $senderType, $senderFirstname, $senderLastname, $senderDocType, $senderDoc, $senderPhone, $senderBusiness, $pickupCity, $pickupDepartament, $pickupAddress, $pickupConfigurationId, $automaticPickup, $freeDelivery);
    }

    private function getCatalogues($totalCatalogs, $status = []) {
        $auxCatalogs = [];
        foreach ($totalCatalogs["data"] as $item) {
            if (in_array($item->progreso, $status)) {
                array_push($auxCatalogs, $item);
            }
        }
        return ["data"=>$auxCatalogs];
    }

    private function getCatalogueCurrency($catalogues)
    {
        foreach ($catalogues["data"] as $c) {
            if ($c->activo) {
                return $c->moneda;
            }
        }
        return "COP";
    }



    private function BBLSucriptionByPlanId($clientId, $planId) {
        $resultado = BBLSuscripcion::select('bbl_suscripciones.*')
            ->leftJoin('bbl_planes as p', 'p.id', '=', 'bbl_suscripciones.bbl_plan_id')
            ->where('bbl_suscripciones.bbl_cliente_id', $clientId)
            ->where('p.id', $planId)
            ->first();

        return $resultado;
    }

    
}