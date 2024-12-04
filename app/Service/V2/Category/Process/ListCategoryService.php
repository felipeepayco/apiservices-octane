<?php
namespace App\Service\V2\Category\Process;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CategoryRepository;
use App\Repositories\V2\ClientRepository;
use App\Helpers\Validation\ValidateUrlImage;
use App\Repositories\V2\ProductRepository;
use Illuminate\Http\Request;

class ListCategoryService extends HelperPago
{

    private $category_repository;
    private $product_repository;
    private $client_repository;

    public function __construct(Request $request,
        CategoryRepository $category_repository,
        ProductRepository $product_repository,
        ClientRepository $client_repository

    ) {
        parent::__construct($request);

        $this->category_repository = $category_repository;
        $this->product_repository = $product_repository;
        $this->client_repository = $client_repository;

    }

    public function handle($params)
    {

        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $pagination = $fieldValidation["pagination"];
            $filters = $fieldValidation["filter"];
            $id = $this->getFieldValidation((array) $filters, "id");
            $name = $this->getFieldValidation((array) $filters, "name");
            $catalogueName = $this->getFieldValidation((array) $filters, "catalogueName");
            $catalogueId = $this->getFieldValidation((array) $filters, "catalogueId");
            $onlyWithProducts = $this->getFieldValidation((array) $filters, "onlyWithProducts", false);
            $origin = $this->getFieldValidation((array) $filters, "origin");
            $countProducts = $this->getFieldValidation((array) $filters, "countProducts", false);
            $page = $this->getFieldValidation((array) $pagination, 'page', 1);
            $pageSize = $this->getFieldValidation((array) $pagination, 'limit', 50);
            $onlyActive = $this->getFieldValidation((array) $filters, 'onlyActive', false);
            $apifyClient = $this->getAlliedEntity($clientId);
            $origin = "epayco";

            $data = $this->category_repository->getCategories($clientId, true, $catalogueId, $catalogueName, $origin, $id, $name);

            $catalogueName = "";
            $paginator = $this->buildCategoryListResponse(
                $data,
                $onlyWithProducts,
                $origin,
                $catalogueName,
                $countProducts,
                $onlyActive
            );

            //Ordenar categorias
            $this->orderData($paginator, $origin);

            //iniciar paginacion manual
            $totalCategories = count($paginator);
            $totalPages = ceil($totalCategories / $pageSize);

            $paginationOffset = $page == 1 ? 0 : ($pageSize * $page) - $pageSize;
            $paginator = array_slice($paginator, $paginationOffset, $pageSize);

            //Consultar subdominio del cliente
            $clientSubdomainSearch = $this->client_repository->find($clientId);

            $clientSubdomain = isset($clientSubdomainSearch->url) ? $clientSubdomainSearch->url : "";

            $newData = [
                "data" => $paginator,
                "current_page" => $page,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "last_page" => $totalPages,
                "next_page_url" => "/catalogue/category?page=" . ($page + 1),
                "path" => $this->getPathByOrigin($origin, $clientSubdomain, $catalogueName),
                "per_page" => $pageSize,
                "prev_page_url" => $page <= 2 ? null : "/catalogue/category?pague=" . ($page - 1),
                "to" => $page <= 1 ? count($paginator) : ($page * $pageSize) - ($pageSize - 1) + (count($paginator) - 1),
                "total" => $totalCategories,
            ];

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error query to database " . $exception->getLine();
            $last_action = 'fetch data from database';
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

    private function buildCategoryListResponse($data, $onlyWithProducts, $origin, &$catalogueName, $countProducts, $onlyActive)
    {

        $paginator = [];
        //$catalogueResult = $category
        foreach ($data as $catalogue) {
            $catalogueName = $catalogue->nombre;
            $updateDate = $this->getFieldValidation((array) $catalogue, 'fecha_actualizacion', $catalogue->fecha);
            $categoriesHits = $catalogue->categorias;
            $categoryCatalogueId = $catalogue->id;

            $categoriesInCatalogue = $this->getCategoriesInCatalogue($categoriesHits);

            foreach ($categoriesHits as $categoryHits) {

                if ($categoryHits["estado"] && $categoryHits["id"] >= 2) {
                    $categorySource = $categoryHits;

                    $newCategory = [
                        "id" => $categorySource["id"],
                        "name" => $categorySource["nombre"],
                        "date" => date("Y-m-d H:i:s", strtotime($categorySource["fecha"])),
                        "catalogueId" => $categoryCatalogueId,
                        "edataStatus" => $this->getFieldValidation((array) $categorySource, 'edata_estado', "Permitido"),
                    ];

                    $this->setEpaycoCategoryData($categorySource, $origin, $newCategory, $catalogueName, $updateDate, $categoriesInCatalogue);

                    if ($onlyWithProducts || $countProducts) {

                        $productsInCategoryResult = $this->product_repository->getCategoriesInProduct($categorySource["id"], $origin);

                        $this->addProductCount($origin, $countProducts, $productsInCategoryResult, $newCategory);

                        if ($this->isValid($origin, $onlyActive, $newCategory, $productsInCategoryResult, $onlyWithProducts)) {
                            array_push($paginator, $newCategory);
                        }
                    } else {
                        array_push($paginator, $newCategory);
                    }
                }

            }
        }

        return $paginator;

    }

    private function isValid($origin, $onlyActive, $category, $productsInCategoryResult, $onlyWithProducts)
    {
        $isValid = true;

        if ($origin == CommonText::ORIGIN_EPAYCO && $onlyActive && !$category[CommonText::ACTIVE_ENG]) {
            $isValid = false;
        }

        if ($onlyWithProducts && empty($productsInCategoryResult["data"])) {
            $isValid = false;
        }
        return $isValid;
    }

    private function getPathByOrigin($origin, $clientSubdomain, $catalogueName)
    {
        $path = $clientSubdomain . "/catalogo/" . urlencode($catalogueName) . "/lista-producto/";
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $path = $clientSubdomain . "/vende/";
        }

        return $path;
    }

    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;

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

    private function getCategoriesInCatalogue($categories)
    {
        $countCategories = 0;
        foreach ($categories as $categoryHits) {
            if ($categoryHits["estado"]) {
                $countCategories++;
            }
        }

        return $countCategories;
    }

    private function setEpaycoCategoryData($categorySource, $origin, &$data, $catalogueName, $updateDate, $categoriesInCatalogue)
    {

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $tempActive = true;
            if (isset($categorySource->activo) && !$categorySource->activo) {
                $tempActive = false;
            }

            $img = $this->getFieldValidation((array) $categorySource, 'img');
            $data["logo"] = $img != "" ? ValidateUrlImage::locateImage($img) : "";
            $data["catalogueName"] = $catalogueName;
            $data["origin"] = CommonText::ORIGIN_EPAYCO;
            $data[CommonText::ACTIVE_ENG] = $this->getFieldValidation((array) $categorySource, 'activo', $tempActive);
            $data["statusCategory"] = $this->getCataegoryStatus($data);
            $data["categoriesInCatalogue"] = $categoriesInCatalogue;
            $data["updateDate"] = date("Y-m-d H:i:s", strtotime($updateDate));
        }
    }

    private function getCataegoryStatus($categoryData)
    {

        $status = $categoryData[CommonText::ACTIVE_ENG] ? "Activo" : "Inactivo";

        return $status;
    }

    private function orderData(&$data, $origin)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            usort($data, function ($item1, $item2) {
                return $item1['date'] < $item2['date'];
            });
        } else {
            usort($data, function ($item1, $item2) {
                return strtolower($item1['name']) > strtolower($item2['name']);
            });
        }
    }
}
