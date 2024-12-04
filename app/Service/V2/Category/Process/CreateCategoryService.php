<?php
namespace App\Service\V2\Category\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\ClientS3;
use App\Helpers\Pago\HelperPago;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Validation\ValidateUrlImage;
use App\Http\Validation\Validate as Validate;

class CreateCategoryService extends HelperPago
{

    
    private $category_repository;
    private $catalogue_repository;

    public function __construct(Request $request,
        CatalogueRepository $catalogue_repository,
        CategoryRepository $category_repository
    ) {

        parent::__construct($request);

        $this->catalogue_repository = $catalogue_repository;
        $this->category_repository = $category_repository;

    }

    public function handle($params)
    {

        try {

            $fieldValidation = $params;
            list($origin, $clientId, $name, $catalogueId, $logo) = $this->listValidation($fieldValidation);

            $catalogue = $this->catalogue_repository->findCatalogue($catalogueId, $clientId);
            $this->validateCatalogueCero($catalogue);

            $this->validateCategoryExist($origin, $catalogueId, $name, $clientId);
            $catalogueName = $catalogue->nombre;

            $urlFile = '';
            if ($origin && $logo != '') {
                list($urlFile) = $this->uploadAws($logo, $clientId, $catalogueName);
            }

            $timeArray = explode(" ", microtime());
            $timeArray[0] = str_replace('.', '', $timeArray[0]);
            $catalogueCategoriesNew = [
                "id" => (int) ($timeArray[1] . substr($timeArray[0], 2, 3)),
                "nombre" => $name,
                "cliente_id" => $clientId,
                "catalogo_id" => $catalogueId,
                "fecha" => date("c"),
                "fecha_actualizacion" => date("c"),
                "img" => $urlFile,
                "estado" => true,
                "activo" => $this->getCategoryIsActive(true),
            ];

            $catalogue->push('categorias', $catalogueCategoriesNew);
            $categoryCreate = $catalogue->save();

            if ($categoryCreate) {

                list($newData) = $this->newData($catalogueCategoriesNew, $origin, $catalogueName);

                $this->setEpaycoDataResponse($newData, $catalogueCategoriesNew["img"], $origin);

                $success = true;
                $title_response = 'Successful category';
                $text_response = 'successful category';
                $last_action = 'successful category';
                $data = $newData;

                $this->deleteCatalogueRedis($catalogueId);

            } else {
                $success = false;
                $title_response = 'Error in create category';
                $text_response = 'Error in create category';
                $last_action = 'delete category';
                $data = [];
            }

        } catch (\Exception $exception) {
            
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $exception->getMessage();
            $validate = new Validate();
            $validate->setError($exception->getCode(), $exception->getMessage());
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);

            Log::info($error);
        } 

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;

    }

    private function deleteCatalogueRedis($catalogueId)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_' . $catalogueId);
        }
    }

    private function getCategoryIsActive($active)
    {

        return $active;
    }

    public function listValidation($fieldValidation)
    {
        $origin = false;
        $clientId = $fieldValidation["clientId"];
        $name = $fieldValidation["name"];
        $catalogueId = $fieldValidation["catalogueId"];
        $logo = isset($fieldValidation["logo"]) ? $fieldValidation["logo"] : "";
        if ($fieldValidation["origin"] == 'epayco') {
            $origin = true;
        }
        return array($origin, $clientId, $name, $catalogueId, $logo);
    }

    public function validateCatalogueCero($catalogueResult)
    {
        if (empty($catalogueResult)) {
            throw new GeneralException("Catalogue not found", [['codError' => 500, 'errorMessage' => 'Catalogue not found']]);
        }
    }

    public function validateCategoryExist($origin, $catalogueId, $categoryName, $clientId)
    {
        if ($origin != "epayco" && (getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") && getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") == "active")) {

            $query = $this->category_repository->checkCategory($catalogueId, $clientId, $categoryName, true);

            $count = $query->count();

            if ($count > 0) {
                throw new GeneralException("category already exist", [['codError' => 500, 'errorMessage' => 'Category already exist']]);
            }
        }
    }

    public function uploadAws($logo, $clientId, $catalogueName)
    {
        $data = explode(',', $logo);
        if (count($data) > 1) {
            $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
            $sacarExt = explode('image/', $data[0]);
            $sacarExt = explode(';', $sacarExt[1]);

            if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                $success = false;
                $title_response = CommonText::FORMAT_NOT_ALLOWED;
                $text_response = CommonText::FORMAT_NOT_ALLOWED;
                $last_action = CommonText::FORMAT_NOT_ALLOWED;
                $data = [];
                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;
            }
            $base64 = base64_decode($data[1]);
            file_put_contents(
                $tmpfname . "." . $sacarExt[0],
                $base64
            );

            $fechaActual = new \DateTime('now');

            //Subir los archivos
            $nameFile = "{$clientId}_{$catalogueName}_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
            $urlFile = "vende/productos/{$nameFile}";
            $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

            $clientS3 = new ClientS3();
            $clientS3->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
            unlink($tmpfname . "." . $sacarExt[0]);
        } else {
            $urlFile = $data[0];
        }
        return array($urlFile);
    }

    private function setEpaycoDataResponse(&$data, $imageRoute, $origin)
    {

        if ($origin) {
            $data["logo"] = $imageRoute != "" ? ValidateUrlImage::locateImage($imageRoute) : "";
            $data["origin"] = 'epayco';
        }

    }

    public function newData($catalogueCategoriesNew, $origin, $catalogueName)
    {
        $newData = [];
        if ($origin) {
            $newData = [
                "id" => $catalogueCategoriesNew["id"],
                "name" => $catalogueCategoriesNew["nombre"],
                "catalogueId" => $catalogueCategoriesNew["catalogo_id"],
                "catalogueName" => $catalogueName,
                "img" => $catalogueCategoriesNew["img"],
                "date" => date("Y-m-d H:i:s", strtotime($catalogueCategoriesNew["fecha"])),
                "edataStatus" => "Permitido",
            ];
        } else {
            $newData = [
                "id" => $catalogueCategoriesNew["id"],
                "name" => $catalogueCategoriesNew["nombre"],
                "catalogueId" => $catalogueCategoriesNew["catalogo_id"],
                "date" => date("Y-m-d H:i:s", strtotime($catalogueCategoriesNew["fecha"])),
                "edataStatus" => "Permitido",
            ];
        }

        return array($newData);
    }
}
