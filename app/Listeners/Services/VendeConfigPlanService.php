<?php

namespace App\Listeners\Services;

use App\Common\ProductClientStateCodes;
use App\Common\SubscriptionStateCodes;
use App\Exceptions\GeneralException;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Messages\CommonText as CM;
use App\Helpers\Pago\HelperPago;
use App\Models\BblPlan;
use App\Models\BblSuscripcion;
use App\Models\Productos;
use App\Models\V2\Product as ProductosMongo;

use App\Models\ProductosClientes;
use App\Models\V2\Catalogue;
use App\Repositories\V2\CatalogueRepository;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use App\Repositories\V2\ProductRepository;

//BBL
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class VendeConfigPlanService extends HelperPago
{
    private const PRODUCTS_ID = "productos.id";
    private const PRODUCTS_CLIENTS_PRODUCT_ID = "productos_clientes.producto_id";
    private const PRODUCTS_CLIENTS_CLIENT_ID = "productos_clientes.cliente_id";
    private const PRODUCTS_CLIENTS_STATE = "productos_clientes.estado";
    private const PRODUCTS_PLAN_TYPE = "productos.tipo_plan";

    //BBL
    private const PLANS = "bbl_planes";
    private const SUBSCRIPTIONS_PLAN_ID = "bbl_suscripciones.bbl_plan_id";
    private const SUBSCRIPTIONS_CLIENT_ID = "bbl_suscripciones.bbl_cliente_id";
    private const SUBSCRIPTIONS_STATUS = "bbl_suscripciones.estado";
    private const PLANS_ID = "bbl_planes.id";

    public function __construct()
    {
        parent::__construct(new Request());
    }

    public function getPlanConfig($planId, $clientProduct = null)
    {
        $product = Productos::where("id", "=", $planId)->first();

        if (is_null($product)) {
            throw new GeneralException("Invalid product", [CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Invalid product']);
        }
        $configuration = json_decode($product->configuracion);

        $allowedCatalogs = $this->getConfigurationValueById($configuration, "vende_product_001");
        $allowedProducts = $this->getConfigurationValueById($configuration, "vende_product_026");
        $allowedAnalitics = $this->getConfigurationValueById($configuration, "vende_product_037");

        $configData = [
            "allowedCatalogs" => $this->getAllowedValue($allowedCatalogs),
            "allowedProducts" => $this->getAllowedValue($allowedProducts),
            "allowedAnalitics" => $allowedAnalitics,
        ];

        if (!is_null($clientProduct)) {
            $configData["planState"] = $clientProduct->estado;
        }

        return $configData;
    }

    public function getBblPlanConfig($plan)
    {

        if (is_null($plan)) {
            throw new GeneralException("Invalid subscription", [CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Invalid subscription']);
        }

        $allowedCatalogs = $plan["tiendas"];
        $allowedProducts = $plan["productos"];
        $allowedCategories = $plan["categorias"];
        $allowedAnalitics = $plan["analitica"];

        $configData = [
            "allowedCatalogs" => $this->getAllowedValue($allowedCatalogs),
            "allowedProducts" => $this->getAllowedValue($allowedProducts),
            "allowedCategories" => $this->getAllowedValue($allowedCategories),
            "allowedAnalitics" => $allowedAnalitics,
        ];
        $configData["planState"] = $plan["estado"];

        return $configData;
    }

    public function getAllowedValue($allowedItems)
    {

        if (strtolower($allowedItems) == "ilimitado") {
            $allowedItems = "ilimitado";
        } else {
            $allowedItems = intval($allowedItems);
        }

        return $allowedItems;
    }

    private function getConfigurationValueById($configuration, $id)
    {
        $columns = array_column($configuration, 'id');
        $targetIndex = array_search($id, $columns);
        return $configuration[$targetIndex]->value;
    }

    public function getTotalActiveCatalogsV2($clientId, $origin, $idCatalogue = null, $publicado = false, $active = false)
    {
        $catalogueRepository = new CatalogueRepository(new Catalogue());
        $filter = [
            'cliente_id' => $clientId,
            'procede' => $origin,
        ];

        if (!$idCatalogue) {
            $filter[CommonText::STATE] = true;

            if ($active) {
                $filter[CommonText::ACTIVE] = true;
            }

            if ($publicado) {
                $filter['progreso'] = 'publicado';
            }
        } else {
            $filter['id'] = $idCatalogue;
        }
        $result = $catalogueRepository->listWithFilterWithOrdenByAsc($filter, CommonText::DATE);

        return $result->toArray();
    }
    public function getTotalActiveCatalogs($clientId, $origin, $idCatalogue = null, $publicado = false, $active = false)
    {
        $search = new Search();
        $search->setSize(10000);
        $search->setFrom(0);

        if (!$idCatalogue) {
            $search->addQuery(new MatchQuery(CommonText::STATE, true), BoolQuery::FILTER);
            if ($active) {
                $search->addQuery(new MatchQuery(CommonText::ACTIVE, true), BoolQuery::FILTER);
            }
            if ($publicado) {
                $search->addQuery(new MatchQuery('progreso', "publicado"), BoolQuery::FILTER);
            }
        } else {
            $search->addQuery(new MatchQuery('id', $idCatalogue), BoolQuery::FILTER);
        }

        $search->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery('procede', $origin), BoolQuery::FILTER);
        $search->addSort(new FieldSort(CommonText::DATE, 'ASC'));

        $catalogueResult = $this->consultElasticSearch($search->toArray(), CommonText::CATALOGUE, false);

        return $catalogueResult["data"];


        

    }

    public function getTotalActiveProducts($catalogs, $origin, $idProduct = null, $clientId = null)
    {

        $search = new Search();
        $search->setSize(10000);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery('origen', $origin), BoolQuery::FILTER);
        if (!$idProduct) {
            if (!is_null($clientId) || empty($catalogs)) {
                $search->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            } else {
                $boolTargetCatalogsForProducts = new BoolQuery();
                foreach ($catalogs as $catalog) {
                    $boolTargetCatalogsForProducts->add(new TermQuery("catalogo_id",is_object($catalog) ? $catalog->id:$catalog["id"]), BoolQuery::SHOULD);
                }
                $search->addQuery($boolTargetCatalogsForProducts);
            }

            $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('activo', true), BoolQuery::FILTER);

            $search->addSort(new FieldSort('fecha', 'ASC'));
            $productResult = $this->consultElasticSearch($search->toArray(), "producto", false);

            return $productResult["data"];
        } else if ($idProduct != null) {
            $search->addQuery(new MatchQuery('origen', $origin), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('id', $idProduct), BoolQuery::FILTER);
            $productResult = $this->consultElasticSearch($search->toArray(), CommonText::PRODUCT, false);

            return $productResult["data"];
        }

        return [];

    }

    public function getTotalActiveProductsV2($catalogs, $origin, $idProduct = null, $clientId = null)
    {
        $query = ProductosMongo::where('origen', $origin);

        if (!$idProduct) {
            if (!is_null($clientId) || empty($catalogs)) {
                $query->where('cliente_id', $clientId);
            } else {
                $catalogIds = [];
                foreach ($catalogs as $catalog) {
                    $catalogIds[] = is_object($catalog) ? $catalog->id : $catalog["id"];
                }
                $query->whereIn('catalogo_id', $catalogIds);
            }

            $query->where('estado', 1)
                ->where('activo', true)
                ->orderBy('fecha', 'asc')
                ->limit(10000);

            return $query->get()->toArray();
        } else if ($idProduct != null) {
            $product = $query->where('id', $idProduct)->first();
            return $product ? [$product->toArray()] : [];
        }

        return [];
    }


 

    public function getTotalProductsByCustomFields($fields)
    {

        $search = new Search();
        $search->setSize(10000);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery(CommonText::STATE, 1), BoolQuery::FILTER);
        foreach ($fields as $field) {
            $search->addQuery(new MatchQuery($field[0], $field[1]), BoolQuery::FILTER);
        }
        $search->addSort(new FieldSort(CommonText::DATE, 'ASC'));
        $productResult = $this->consultElasticSearch($search->toArray(), CommonText::PRODUCT, false);

        return $productResult["data"];

    }

    public function disableCatalogsOverCurrentPlan($catalogs)
    {

        if (!empty($catalogs)) {
            $targetCatalogs = new Search();
            $boolTargetCatalog = new BoolQuery();
            $boolTargetCatalogsForProducts = new BoolQuery();

            foreach ($catalogs as $catalog) {
                $boolTargetCatalog->add(new TermQuery("id", $catalog->id), BoolQuery::SHOULD);
                $boolTargetCatalogsForProducts->add(new TermQuery(CommonText::CATALOGUE_ID, $catalog->id), BoolQuery::SHOULD);
            }

            $targetCatalogs->addQuery($boolTargetCatalog);

            $updateData = $targetCatalogs->toArray();
            $inlines = [
                "ctx._source.activo=false;ctx._source.estado_plan='activo'",
                "if(ctx._source.categorias !== null) {for(category in ctx._source.categorias) { category.activo = false }}",
            ];

            $updateData["script"] = ["inline" => implode(";", $inlines)];
            $updateData[CommonText::INDEX] = CommonText::CATALOGUE;

            $this->elasticUpdate($updateData);

            $targetProducts = new Search();
            $targetProducts->addQuery($boolTargetCatalogsForProducts);

            $updateProductData = $targetProducts->toArray();
            $inlines = [
                "ctx._source.activo=false",
            ];

            $updateProductData["script"] = ["inline" => implode(";", $inlines)];
            $updateProductData[CommonText::INDEX] = CommonText::PRODUCT;

            $this->elasticUpdate($updateProductData);

        }
    }

    public function enableAllCataloguePlanStatus($clientId)
    {
        $search = new Search();
        $search->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery('procede', "epayco"), BoolQuery::FILTER);
        $updateData = $search->toArray();
        $inlines = ["ctx._source.estado_plan='activo'"];

        $updateData["script"] = ["inline" => implode(";", $inlines)];
        $updateData[CommonText::INDEX] = CommonText::CATALOGUE;

        $this->elasticUpdate($updateData);
    }

    public function enabledCataloguePlanStatus($catalogs)
    {

        if (!empty($catalogs)) {
            $targetCatalogs = new Search();
            $boolTargetCatalog = new BoolQuery();
            $boolTargetCatalogsForProducts = new BoolQuery();

            foreach ($catalogs as $catalog) {
                $boolTargetCatalog->add(new TermQuery("id", $catalog->id), BoolQuery::SHOULD);
                $boolTargetCatalogsForProducts->add(new TermQuery(CommonText::CATALOGUE_ID, $catalog->id), BoolQuery::SHOULD);
            }

            $targetCatalogs->addQuery($boolTargetCatalog);

            $updateData = $targetCatalogs->toArray();
            $inlines = [
                "ctx._source.estado_plan='activo'",
            ];

            $updateData["script"] = ["inline" => implode(";", $inlines)];
            $updateData[CommonText::INDEX] = CommonText::CATALOGUE;

            $this->elasticUpdate($updateData);
        }
    }

    public function disableProductsOverCurrentPlan($products)
    {

        if (!empty($products)) {
            $targetProducts = new Search();
            $boolTargetProduct = new BoolQuery();

            foreach ($products as $product) {
                $boolTargetProduct->add(new TermQuery("id", $product->id), BoolQuery::SHOULD);
            }

            $targetProducts->addQuery($boolTargetProduct);

            $updateData = $targetProducts->toArray();
            $inlines = [
                "ctx._source.activo=false",
            ];

            $updateData["script"] = ["inline" => implode(";", $inlines)];
            $updateData[CommonText::INDEX] = CommonText::PRODUCT;

            $this->elasticUpdate($updateData);

        }
    }

    public function inactivateCategoriesWithoutProducts($catalogs)
    {

        $productsInCategories = new Search();
        $productsInCategories->setSize(10000);
        $productsInCategories->setFrom(0);

        $boolFilterCategoriesQuery = new BoolQuery();

        $catalogsId = [];
        foreach ($catalogs as $catalog) {
            array_push($catalogsId, $catalog->id);
        }

        $productsInCategories->addQuery(new MatchQuery(CommonText::STATE, 1), BoolQuery::FILTER);
        $productsInCategories->addQuery(new MatchQuery(CommonText::ACTIVE, true), BoolQuery::FILTER);
        $productsInCategories->addQuery(new TermsQuery("catalogo_id", $catalogsId), BoolQuery::FILTER);

        $categoriesTermAggregation = new TermsAggregation('agg_categories');
        $categoriesTermAggregation->setField('categorias');
        $productsInCategories->addAggregation($categoriesTermAggregation);

        $productsInCategoriesResponse = $this->consultElasticSearch($productsInCategories->toArray(), CommonText::PRODUCT, false);
        $aggregationData = $productsInCategoriesResponse["aggregations"]->agg_categories->buckets;
        $idCategoriesWithProducts = [];

        foreach ($aggregationData as $categoryWithProduct) {
            if ($categoryWithProduct->key !== 1) {
                array_push($idCategoriesWithProducts, $categoryWithProduct->key);
            }
        }

        if (!empty($idCategoriesWithProducts)) {
            $searchCategoriesForUpdate = new Search();
            $boolFilterCategoriesQuery->add(new MatchQuery(CommonText::STATE, true), BoolQuery::FILTER);
            $boolFilterCategoriesQuery->add(new MatchQuery(CommonText::ACTIVE, true), BoolQuery::FILTER);
            $boolFilterCategoriesQuery->add(new TermsQuery("id", $catalogsId), BoolQuery::FILTER);
            $searchCategoriesForUpdate->addQuery($boolFilterCategoriesQuery);

            $updateCategory = $searchCategoriesForUpdate->toArray();

            $inlines = [
                "if(ctx._source.categorias !== null) {def targets = ctx._source.categorias.findAll(category -> !params.ids.contains(category.id)); for(category in targets) { category.activo = false }}",
            ];

            $updateCategory["script"] = [
                "inline" => implode(";", $inlines),
                "params" => ["ids" => $idCategoriesWithProducts],
            ];

            $updateCategory[CommonText::INDEX] = CommonText::CATALOGUE;

            $this->elasticUpdate($updateCategory);
        }

    }

    public function validatePlan($clientId)
    {

        //obtengo el plan
        $plan = $this->getPlanBblById($clientId);

        if (!$plan) {
            return $plan;
        }

        //retorno la configuracion del plan
        return $this->getBblPlanConfig($plan);

    }

    public function getPlanBblById($clientId, $tipo = CM::TIPO_PLAN)
    {

        $plan = BblPlan::join(CommonText::BBL_SUBSCRIPTIONS, $this::PLANS_ID, "=", $this::SUBSCRIPTIONS_PLAN_ID)
            ->where($this::SUBSCRIPTIONS_CLIENT_ID, $clientId)
            ->where($this::SUBSCRIPTIONS_STATUS, ProductClientStateCodes::ACTIVE)
            ->select($this::PLANS . '.*')
            ->first();

        if (is_null($plan)) {

            $plan = BblPlan::join(CommonText::BBL_SUBSCRIPTIONS, $this::PLANS_ID, "=", $this::SUBSCRIPTIONS_PLAN_ID)
                ->where($this::SUBSCRIPTIONS_CLIENT_ID, $clientId)
                ->where($this::SUBSCRIPTIONS_STATUS, ProductClientStateCodes::INTEGRATION)
                ->select($this::PLANS . '.*')
                ->first();

        }

        return $plan;
    }

    public function getPlanById($clientId, $tipo = CM::TIPO_PLAN)
    {

        $plan = ProductosClientes::join(CommonText::PRODUCTS, $this::PRODUCTS_ID, "=", $this::PRODUCTS_CLIENTS_PRODUCT_ID)
            ->where($this::PRODUCTS_CLIENTS_CLIENT_ID, $clientId)
            ->where($this::PRODUCTS_CLIENTS_STATE, ProductClientStateCodes::ACTIVE)
            ->where($this::PRODUCTS_PLAN_TYPE, $tipo)->first();

        if (is_null($plan)) {
            $plan = ProductosClientes::join(CommonText::PRODUCTS, $this::PRODUCTS_ID, "=", $this::PRODUCTS_CLIENTS_PRODUCT_ID)
                ->where($this::PRODUCTS_CLIENTS_CLIENT_ID, $clientId)
                ->where($this::PRODUCTS_CLIENTS_STATE, ProductClientStateCodes::INTEGRATION)
                ->where($this::PRODUCTS_PLAN_TYPE, $tipo)->first();
        }

        return $plan;
    }

    public function getClientActivePlanBbl($clientId)
    {

        return $suscripcion = BblSuscripcion::where($this::SUBSCRIPTIONS_CLIENT_ID, $clientId)
            ->where($this::SUBSCRIPTIONS_STATUS, SubscriptionStateCodes::ACTIVE)
            ->first();

    }

    public function getClientActivePlan($clientId, $type = CM::TIPO_PLAN)
    {
        return ProductosClientes::join(CommonText::PRODUCTS, $this::PRODUCTS_ID, "=", $this::PRODUCTS_CLIENTS_PRODUCT_ID)
            ->where($this::PRODUCTS_CLIENTS_CLIENT_ID, $clientId)
            ->where($this::PRODUCTS_CLIENTS_STATE, ProductClientStateCodes::ACTIVE)
            ->where($this::PRODUCTS_PLAN_TYPE, $type)->first();
    }

    public function getPlanActiveAndDateToday($clientId, $bblPlanId = CM::TIPO_PLAN)
    {
        $fecha = new \DateTime("now");
        $plan = BblSuscripcion::where("bbl_cliente_id", $clientId)
            ->where("estado", ProductClientStateCodes::ACTIVE)
            ->where("fecha_inicio", $fecha->format('Y-m-d'))
            ->where("bbl_plan_id", $bblPlanId)->first();

        if (is_null($plan)) {
            $plan = BblSuscripcion::where("bbl_cliente_id", $clientId)
                ->where("estado", ProductClientStateCodes::ACTIVE_PENDING)
                ->where("fecha_inicio", $fecha->format('Y-m-d'))
                ->where("bbl_plan_id", $bblPlanId)->first();
        }

        return $plan;
    }

    public function activeCatalogueOrProductAfterEdataAllowedMongoDB($itemData, $index = null)
    {
        $activeAfterEdataAllowed = false;
        $itemId = isset($itemData["id"]) ? $itemData["id"] : null;

        if ($index == "catalogue") {
            $targetRequestParams = "name";
            $targetElasticParams = "nombre";
            $elasticIndex = CommonText::CATALOGUE;
        } else {
            $targetRequestParams = "title";
            $targetElasticParams = "titulo";
            $elasticIndex = CommonText::PRODUCT;
        }

        if (!is_null($itemId)) {
            $catalogueRepository = new CatalogueRepository(new Catalogue());
            $result = $catalogueRepository->findByIdAndClientIdNoEstatus($itemData["id"], $itemData["clientId"]);
            $itemResult = $result->toArray();

            if (isset($itemResult["data"]) && !empty($itemResult["data"])) {
                $currentItemData = (array) $itemResult["data"][0];

                $activeAfterEdataAllowed = $this->validateIsActivatedRequest($itemData, $currentItemData, $targetRequestParams, $targetElasticParams, $index);
            }
        }

        return $activeAfterEdataAllowed;
    }
    public function activeCatalogueOrProductAfterEdataAllowed($itemData, $index = null)
    {
        $activeAfterEdataAllowed = false;
        $itemId = isset($itemData["id"]) ? $itemData["id"] : null;

        if ($index == "catalogue") {
            $targetRequestParams = "name";
            $targetElasticParams = "nombre";
            $elasticIndex = CommonText::CATALOGUE;
        } else {
            $targetRequestParams = "title";
            $targetElasticParams = "titulo";
            $elasticIndex = CommonText::PRODUCT;
        }

        if (!is_null($itemId)) {
            $search = new Search();
            $search->setSize(1);
            $search->setFrom(0);

            $search->addQuery(new MatchQuery(CommonText::CLIENT_ID, $itemData["clientId"]), BoolQuery::FILTER);
            $search->addQuery(new MatchQuery('id', $itemData["id"]), BoolQuery::FILTER);

            $itemResult = $this->consultElasticSearch($search->toArray(), $elasticIndex, false);

            if (isset($itemResult["data"]) && !empty($itemResult["data"])) {
                $currentItemData = (array) $itemResult["data"][0];

                $activeAfterEdataAllowed = $this->validateIsActivatedRequest($itemData, $currentItemData, $targetRequestParams, $targetElasticParams, $index);
            }

        }

        return $activeAfterEdataAllowed;
    }

    public function activeCategoryAfterEdataAllowed($itemData)
    {
        $activeAfterEdataAllowed = false;
        $itemId = isset($itemData["id"]) ? $itemData["id"] : null;

        if (!is_null($itemId)) {

            $searchCategory = new Search();
            $searchCategory->setSize(1);
            $searchCategory->setFrom(0);
            $searchCategory->addQuery(new MatchQuery(CommonText::CLIENT_ID, $itemData["clientId"]), BoolQuery::MUST);
            $searchCategory->addQuery(new MatchQuery('id', $itemData["catalogueId"]), BoolQuery::MUST);

            $matchQueryCategoryId = new MatchQuery('categorias.id', $itemData["id"]);
            $boolQuery = new BoolQuery();
            $boolQuery->add($matchQueryCategoryId);

            $nestedQuery = new NestedQuery(
                'categorias',
                $boolQuery
            );

            $nestedQuery->addParameter('inner_hits', ["_source" => true]);

            $searchCategory->addQuery($nestedQuery);
            $searchCategoryResult = $this->consultElasticSearch($searchCategory->toArray(), CommonText::CATALOGUE, false);

            if (isset($searchCategoryResult["data"]) && !empty($searchCategoryResult["data"])) {

                $category = (array) $searchCategoryResult["data"][0]->inner_hits->categorias->hits->hits[0]->_source;

                $activeAfterEdataAllowed = $this->validateIsActivatedRequest($itemData, $category, "name", "nombre");
            }

        }

        return $activeAfterEdataAllowed;
    }

    private function validateIsActivatedRequest($itemData, $currentItemData, $targetRequestParam, $targetElasticParam, $index = null)
    {
        $isActivatedRequest = false;

        if (
            (
                ($index == "catalogue") ||
                (
                    (!isset($currentItemData[CommonText::ACTIVE]) || (isset($currentItemData[CommonText::ACTIVE]) && $currentItemData[CommonText::ACTIVE] === false))
                    &&
                    (isset($itemData["active"]) && $itemData["active"] === true)
                )
            ) && (
                $currentItemData[$targetElasticParam] == $itemData[$targetRequestParam]
            ) && (
                isset($currentItemData[HelperEdata::EDATA_STATE_BEFORE]) && (
                    $currentItemData[HelperEdata::EDATA_STATE_BEFORE] === HelperEdata::STATUS_ALERT ||
                    $currentItemData[HelperEdata::EDATA_STATE_BEFORE] === HelperEdata::STATUS_BLOCK
                ) &&
                (
                    $currentItemData["edata_estado"] === HelperEdata::STATUS_ALLOW
                )
            )
        ) {
            $isActivatedRequest = true;
        }

        return $isActivatedRequest;
    }

    public function setOriginByAlliedEntity($apifyClient, &$origin)
    {
        $origin = "epayco";
    }

    public function disableAnalitics($clientId, $allowedAnalitics)
    {
        if (!$allowedAnalitics) {
            $search = new Search();
            $search->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
            $updateData = $search->toArray();

            $inlines = [
                "ctx._source.analiticas=params.analiticas",
            ];

            $params = [
                "analiticas" => [
                    "google_tag_manager_id" => "",
                    "facebook_pixel_active" => false,
                    "facebook_pixel_id" => "",
                    "google_tag_manager_active" => false,
                    "google_analytics_active" => false,
                    "google_analytics_id" => "",
                ],
            ];

            $updateData["script"] = ["inline" => implode(";", $inlines), "params" => $params];
            $updateData[CommonText::INDEX] = CommonText::CATALOGUE;
            $this->elasticUpdate($updateData);
        }
    }

    public function getTotalProductsByCustomFieldsV2($fields)
    {
        $query = ProductosMongo::where(CommonText::STATE, 1);

        foreach ($fields as $field) {
            $query->where($field[0], $field[1]);
        }

        $query->orderBy(CommonText::DATE, 'asc')->limit(10000);

        return $query->get()->toArray();

    }

    public function activeCategoryAfterEdataAllowedV2($itemData)
    {
        $itemId = $itemData["id"] ?? null;

        if (!is_null($itemId)) {
            $catalogue = Catalogue::where(CommonText::CLIENT_ID, $itemData["clientId"])
                ->where('id', $itemData["catalogueId"])
                ->where('categorias.id', $itemData["id"])
                ->first();

            if ($catalogue) {
                $category = collect($catalogue->categorias)->firstWhere('id', $itemData["id"]);

                if ($category) {
                    return $this->validateIsActivatedRequest($itemData, (array) $category, "name", "nombre");
                }
            }
        }

        return false;
    }
}
