<?php
namespace App\Service\V2\Category\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CategoryRepository;
use App\Repositories\V2\ProductRepository;

use Illuminate\Http\Request;

class DeleteCategoryService extends HelperPago
{

    private $category_repository;
    private $product_repository;

    public function __construct(Request $request,
    CategoryRepository $category_repository,
    ProductRepository $product_repository
    )
    {

        parent::__construct($request);

        $this->category_repository = $category_repository;
        $this->product_repository = $product_repository;
    }

    public function handle($params)
    {

        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];

            $categories = $this->category_repository->categoriesInCatalogue($clientId, $id, true, 10);

            if (count($categories) > 0) {

                $category = $categories->first();

                $categoryUpdated = $this->category_repository->updateCategories($clientId, $id, false);

                if ($this->validateLastCategory($category)) {
                    $category["progreso"] = 'completado';
                }

                $category->save();

                if ($categoryUpdated) {
                    $this->deleteCatalogueRedis($category["catalogo_id"]);

                    $this->migrateProductsToGeneralCategory($id);
                    $success = true;
                    $title_response = 'Successful delete category';
                    $text_response = 'successful delete category';
                    $last_action = 'delete category';
                    $data = [];
                } else {
                    $success = false;
                    $title_response = 'Error delete category';
                    $text_response = 'Error delete category, category not found';
                    $last_action = 'delete sell';
                    $data = [];
                }
            } else {
                $success = false;
                $title_response = 'Error delete category';
                $text_response = 'Error delete category, category not found';
                $last_action = 'delete sell';
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al eliminar las categorias con los parametros datos";
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

    private function deleteCatalogueRedis($catalogueId)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_' . $catalogueId);
        }
    }

    private function validateLastCategory($catalogue)
    {

        $categories = $catalogue["categorias"];
        $countCategories = 0;

        if (isset($catalogue["procede"]) && $catalogue["procede"] == "epayco") {
            foreach ($categories as $category) {
                if ($category["id"] !== 1 && $category["estado"]) {
                    $countCategories = $countCategories + 1;
                }
            }

            return $countCategories === 1;
        }
    }

    private function migrateProductsToGeneralCategory($categoryId)
    {

        $this->product_repository->updateCategoriesInProduct($categoryId, false);
    }
}
