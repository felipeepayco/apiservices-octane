<?php
namespace App\Service\V2\Category\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\ClientS3;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\CategoryRepository;
use App\Repositories\V2\ProductRepository;
use Exception;
use Illuminate\Http\Request;
use App\Helpers\Validation\ValidateUrlImage;
use Illuminate\Support\Facades\Log;

class UpdateCategoryService extends HelperPago
{

    private $catalogue_repository;
    private $product_repository;
    private $category_repository;

    public function __construct(Request $request,
        CatalogueRepository $catalogue_repository,
        CategoryRepository $category_repository,
        ProductRepository $product_repository,

    ) {

        parent::__construct($request);
        $this->category_repository = $category_repository;
        $this->catalogue_repository = $catalogue_repository;
        $this->product_repository = $product_repository;
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle($params)
    {

        try {

            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $name = $fieldValidation["name"];
            $catalogueId = (int) $fieldValidation["catalogueId"];
            $categoryId = (int) $fieldValidation["id"];
            $origin = $this->getFieldValidation($fieldValidation, "origin");
            $logo = $this->getFieldValidation($fieldValidation, "logo");
            $active = $this->getFieldValidation($fieldValidation, CommonText::ACTIVE_ENG, true);

            $this->validateCategoryExist($origin, $catalogueId, $name, $categoryId, $clientId);
            $catalogueResult = $this->catalogue_repository->getCatalogues($catalogueId, $clientId, $origin, 10);
            if ($catalogueResult->isEmpty()) {

                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "error";
                $arr_respuesta['textResponse'] = "Catalogue not found";
                $arr_respuesta['lastAction'] = "Update category";
                $arr_respuesta['data'] = [];

                return $arr_respuesta;
            }
            $catalogueName = $catalogueResult[0]->nombre;
            $searchCategoryExistResult = $this->category_repository->categoriesInCatalogue($clientId, $categoryId, true, 10);

            if ($searchCategoryExistResult->isEmpty()) {

                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "error";
                $arr_respuesta['textResponse'] = "category not found";
                $arr_respuesta['lastAction'] = "Update category";
                $arr_respuesta['data'] = [];

                return $arr_respuesta;
            }

            $document = $searchCategoryExistResult->first();
            $category = null;

            if ($document && isset($document->categorias)) {
                foreach ($document->categorias as $cat) {
                    if ($cat['id'] == $categoryId) {
                        $category = $cat;
                        break;
                    }
                }
            }
            if (is_null($category)) {

                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "error";
                $arr_respuesta['textResponse'] = "category not found within catalogue";
                $arr_respuesta['lastAction'] = "Update category";
                $arr_respuesta['data'] = [];

                return $arr_respuesta;
            }

            $categoryImage = $this->getFieldValidation((array) $category, "img");

            $imageRoute = $this->uploadAws($logo, $clientId, $name, $categoryImage, $origin);

            $catalogueToUpdate = $this->category_repository->updateCategoriesByCatalogueId($catalogueId, $categoryId, $name, $imageRoute, $this->getCategoryIsActive($active));

            $countEnabledProducts = 0;
            if ($category["activo"]) {
                $this->changeStatusProductsInCategory($active, $category, $clientId, $countEnabledProducts);
            }

            $newData = [
                "name" => $name,
                "id" => $category["id"],
                "catalogueId" => $catalogueId,
                "date" => date("Y-m-d H:i:s", strtotime($category["fecha"])),
                "edataStatus" => "Permitido",
            ];
            $this->setEpaycoDataResponse($newData, $imageRoute, $origin, $catalogueName, $active, $countEnabledProducts);

            $success = true;
            $title_response = 'Successful category';
            $text_response = 'successful category';
            $last_action = 'successful category';
            $data = $newData;

            $redis = app('redis')->connection();
            $exist = $redis->exists('vende_catalogue_' . $catalogueId);
            if ($exist) {
                $redis->del('vende_catalogue_' . $catalogueId);
            }

        } catch (Exception $exception) {

            Log::info($exception);
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        } catch (GeneralException $generalException) {

            Log::info($generalException);

            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "UpdateCatalogueGeneralException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;

    }

    public function validateCategoryExist($origin, $catalogueId, $categoryName, $categoryId, $clientId)
    {
        if ($origin != CommonText::ORIGIN_EPAYCO &&
            (getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") && getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") == CommonText::ACTIVE_ENG)) {

            $catalogue = $this->category_repository->checkCategory($catalogueId, $clientId, $categoryName, true);

            if ($catalogue) {
                $categoryData = $catalogue->categorias; // This would return an array of categories within the catalogue.

                // Here, we need to loop and find the exact category with the given name.
                foreach ($categoryData as $category) {
                    if ($category['nombre'] == $categoryName && $category['id'] != $categoryId) {
                        throw new GeneralException("category already exist", [['codError' => 500, 'errorMessage' => 'Category alredy exist']]);
                    }
                }
            }
        }
    }

    public function uploadAws($logo, $clientId, $categoryName, $categoryLogo, $origin)
    {

        $imageRoute = $categoryLogo;

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            if ($logo == "delete") {
                $imageRoute = "";
            } else if ($logo != "" && (strpos($logo, "https") !== 0)) {
                $data = explode(',', $logo);
                if (count($data) > 1) {
                    $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                    $sacarExt = explode('image/', $data[0]);
                    $sacarExt = explode(';', $sacarExt[1]);

                    if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                        throw new GeneralException("file format not allowed");
                    }

                    $base64 = base64_decode($data[1]);
                    file_put_contents(
                        $tmpfname . "." . $sacarExt[0],
                        $base64
                    );

                    $fechaActual = new \DateTime('now');

                    //Subir los archivos
                    $nameFile = "{$clientId}_{$categoryName}_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                    $urlFile = "vende/productos/{$nameFile}";
                    $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

                    $clientS3 = new ClientS3();
                    $clientS3->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
                    unlink($tmpfname . "." . $sacarExt[0]);
                    $imageRoute = $urlFile;
                } else {
                    $imageRoute = $data[0];
                }
            }
        }

        return $imageRoute;
    }

    private function setEpaycoDataResponse(&$data, $imageRoute, $origin, $catalogueName, $active, $countEnabledProducts)
    {

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $data["logo"] = $imageRoute != "" ? ValidateUrlImage::locateImage($imageRoute) : "";
            $data["origin"] = $origin;
            $data["catalogueName"] = $catalogueName;
            $data[CommonText::ACTIVE_ENG] = $this->getCategoryIsActive($active);
            $data["countEnabledProducts"] = $countEnabledProducts;
        }
    }

    private function getCategoryIsActive($active)
    {

        return $active;
    }

    private function changeStatusProductsInCategory($active, $category, $clientId, &$countEnabledProducts)
    {
        if ($this->validateIsChangeStatusProductsInCategory($category, $active)) {

            $vendeConfigPlanService = new VendeConfigPlanService();
            $clientPlan = $vendeConfigPlanService->getClientActivePlanBbl($clientId);

            if (!is_null($clientPlan)) {
                $planConfig = $vendeConfigPlanService->getBblPlanConfig($clientPlan);
                $allowedProducts = $planConfig["allowedProducts"];

                if ($active && $allowedProducts !== "ilimitado") {
                    $currentActiveProducts = $vendeConfigPlanService->getTotalProductsByCustomFieldsV2([
                        [CommonText::CLIENT_ID, $clientId],
                        [CommonText::ACTIVE, true],
                        ["origen", CommonText::ORIGIN_EPAYCO],
                    ]);

                    $productsInCategory = $vendeConfigPlanService->getTotalProductsByCustomFieldsV2([
                        [CommonText::CATEGORIES, $category["id"]],
                        [CommonText::ACTIVE, false],
                        ["origen", CommonText::ORIGIN_EPAYCO],
                    ]);

                    $countCurrentActiveProducts = count($currentActiveProducts);
                    $countAllowedActiveProducts = $allowedProducts - $countCurrentActiveProducts;
                    $enabledProducts = array_slice($productsInCategory, 0, $countAllowedActiveProducts);
                    $enabledProductsId = collect($enabledProducts)->pluck('id')->toArray();

                    $this->product_repository->updateCategoriesInProductById($enabledProductsId, $active);

                } else {

                    $this->product_repository->updateCategoriesInProduct($category["id"], $active);

                }

                if ($active) {
                    $countEnabledProducts = $this->product_repository->countCategoriesInProduct($category["id"], true);

                }
            }
        }
    }

    private function validateIsChangeStatusProductsInCategory($category, $active)
    {
        return ((!isset($category["activo"]) && $active === false) || $category["activo"] !== $active);
    }

}
