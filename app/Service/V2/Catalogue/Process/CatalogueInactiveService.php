<?php

namespace App\Service\V2\Catalogue\Process;

use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\CatalogueRepository;
use App\Listeners\Services\VendeConfigPlanService;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;

use App\Helpers\Validation\CommonValidation;



use App\Repositories\V2\CategoryRepository;

class CatalogueInactiveService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected CategoryRepository $categoryRepository;

    public function __construct(CatalogueRepository $catalogueRepository, ProductRepository $productRepository, CategoryRepository $categoryRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }
    public function process($data)
    {

        try {
            $fieldValidation = $data;
            $suspended = CommonValidation::getFieldValidation($fieldValidation, "suspended", false);
            $clientId = $fieldValidation["clientId"];
            $statusActivo = "";
            $estadoPlan = "";
            if ($suspended) {
                $vendeConfigPlan = new VendeConfigPlanService();
                $plan = $vendeConfigPlan->getPlanActiveAndDateToday($clientId);
                if (is_null($plan)) {
                    $estadoPlan = 'suspendido';
                }
            } else {
                $statusActivo = "false";
            }
            $result = $this->catalogueRepository->updateInactive($clientId, $estadoPlan, $statusActivo);


            if ($result) {
                if (!$suspended) {
                    $this->inactiveCategories($clientId);
                    $this->inactiveProducts($clientId);
                }
                $success = true;
                $title_response = 'Successful inactive catalogue';
                $text_response = 'successful inactive catalogue';
                $last_action = 'inactive catalogue';
                $data = [];
            } else {
                $success = false;
                $title_response = 'Error inactive catalogue';
                $text_response = 'Error inactive catalogue, catalogue not found';
                $last_action = 'inactive catalogue';
                $data = [
                    "totalErrors" => 1,
                    "errors" => [
                        [
                            "codError" => 500,
                            "errorMessage" => "Error inactive catalogue, catalogue not found"
                        ]
                    ]
                ];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado " . $exception->getMessage();
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    // Colocar categorias de ese catalogo en Inactivo


    private function inactiveCategories($clientId)
    {

        try {

            $this->categoryRepository->inactiveByClientId($clientId, true);
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los cobros con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    // Colocar productos de esa las categorias en inactivo

    private function inactiveProducts($clientId)
    {

        try {
            $this->productRepository->inactiveByClientId($clientId);
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los cobros con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}
