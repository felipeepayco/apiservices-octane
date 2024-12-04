<?php
namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Service\V2\Category\Process\CreateCategoryService;
use App\Service\V2\Category\Process\DeleteCategoryService;
use App\Service\V2\Category\Process\ListCategoryService;
use App\Service\V2\Category\Process\UpdateCategoryService;
use App\Service\V2\Category\Validations\CreateCategoryValidation;
use App\Service\V2\Category\Validations\DeleteCategoryValidation;
use App\Service\V2\Category\Validations\ListCategoryValidation;
use App\Service\V2\Category\Validations\UpdateCategoryValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiCatalogueCategoriesControllerv2 extends HelperPago
{

    public function __construct(
        Request $request
    ) {
        parent::__construct($request);
    }
    public function catalogueCategoriesList(Request $request, ListCategoryService $list_category_service)
    {
        try {

            $list_category_validation = new ListCategoryValidation($request);
            $validationGeneralCatalogueCategoryList = $list_category_validation->handle($request);

            if (!$validationGeneralCatalogueCategoryList["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueCategoryList);
            }

            $consulCatalogueCategory = $list_category_service->handle($validationGeneralCatalogueCategoryList);

            $success = $consulCatalogueCategory['success'];
            $title_response = $consulCatalogueCategory['titleResponse'];
            $text_response = $consulCatalogueCategory['textResponse'];
            $last_action = $consulCatalogueCategory['lastAction'];
            $data = $consulCatalogueCategory['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

    public function catalogueCategoriesNew(Request $request, CreateCategoryService $create_category_service)
    {
        try {



            $create_category_validation = new CreateCategoryValidation($request);
            $validate = $create_category_validation->handle($request);

            if (!$validate["success"]) {
                return $this->crearRespuesta($validate);
            }

            $create = $create_category_service->handle($validate["data"]);

            $success = $create['success'];
            $title_response = $create['titleResponse'];
            $text_response = $create['textResponse'];
            $last_action = $create['lastAction'];
            $data = $create['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database";
            $last_action = "NA" . $exception->getLine();
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

    public function catalogueCategoriesDelete(Request $request, DeleteCategoryService $delete_category_service)
    {
        try {
            $delete_category_validation = new DeleteCategoryValidation($request);

            $validationGeneralCatalogueCategoriesDelete = $delete_category_validation->handle($request);
            if (!$validationGeneralCatalogueCategoriesDelete["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueCategoriesDelete);
            }

            $consultSellDelete = $delete_category_service->handle($validationGeneralCatalogueCategoriesDelete);

            $success = $consultSellDelete['success'];
            $title_response = $consultSellDelete['titleResponse'];
            $text_response = $consultSellDelete['textResponse'];
            $last_action = $consultSellDelete['lastAction'];
            $data = $consultSellDelete['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }

    public function catalogueCategoriesUpdate(Request $request, UpdateCategoryService $update_category_service, UpdateCategoryValidation $update_category_validation)
    {
        try {
            $validationGeneralCatalogueCategoriesUpdate = $update_category_validation->handle($request);
            if (!$validationGeneralCatalogueCategoriesUpdate["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueCategoriesUpdate);
            }

            $consultCatalogueCategoriesUpdate = $update_category_service->handle($validationGeneralCatalogueCategoriesUpdate["data"]);

            $success = $consultCatalogueCategoriesUpdate['success'];
            $title_response = $consultCatalogueCategoriesUpdate['titleResponse'];
            $text_response = $consultCatalogueCategoriesUpdate['textResponse'];
            $last_action = $consultCatalogueCategoriesUpdate['lastAction'];
            $data = $consultCatalogueCategoriesUpdate['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion";
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }
}
