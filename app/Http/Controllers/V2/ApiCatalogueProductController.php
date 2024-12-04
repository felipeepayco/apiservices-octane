<?php
namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use App\Service\V2\Product\Process\CreateProductService;
use App\Service\V2\Product\Process\DeleteProductService;
use App\Service\V2\Product\Process\ListProductService;
use App\Service\V2\Product\Process\ToggleProductService;
use App\Service\V2\Product\Process\TopSellingProductService;
use App\Service\V2\Product\Validations\CreateProductValidation;
use App\Service\V2\Product\Validations\DeleteProductValidation;
use App\Service\V2\Product\Validations\ListProductValidation;
use App\Service\V2\Product\Validations\ToggleProductValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiCatalogueProductController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }
    public function listproductsElastic(Request $request, ListProductValidation $listProductValidation, ListProductService $listProductService)
    {
        try {
            if (!$listProductValidation->validate($request)) {
                return $this->crearRespuesta($listProductValidation->response);
            }

            $listProductResult = $listProductService->process($listProductValidation->response);
            $success = $listProductResult['success'];
            $title_response = $listProductResult['titleResponse'];
            $text_response = $listProductResult['textResponse'];
            $last_action = $listProductResult['lastAction'];
            $body = $listProductResult['data'];
            $data = empty($body) ? $body : $body["data"];
            if (!empty($body)) {
                unset($body["data"]);
            }
            $paginateInfo = $body;
        } catch (\Exception $exception) {

            Log::info($exception);
            $success = false;
            $title_response = "Error" . $exception->getFile();
            $text_response = "Error query database" . $exception->getMessage();
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
            'paginate_info' => $paginateInfo ?? "",

        );
        return $this->crearRespuesta($response);
    }

    public function catalogueProductNewElastic(Request $request, CreateProductValidation $createProductValidation, CreateProductService $createProductService)
    {
        try {

            $createProductValidation = $createProductValidation->validate($request);
            if (!$createProductValidation["success_validation"]) {
                return $this->crearRespuesta($createProductValidation);
            }


            $createProductResult = $createProductService->process($createProductValidation["data"], $request);

            $success = $createProductResult['success'];
            $title_response = $createProductResult['titleResponse'];
            $text_response = $createProductResult['textResponse'];
            $last_action = $createProductResult['lastAction'];
            $data = $createProductResult['data'];

        } catch (Exception $exception) {

            Log::info($exception);

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

    public function catalogueProductElasticDelete(Request $request, DeleteProductValidation $deleteProductValidation, DeleteProductService $deleteProductService)
    {
        try {
            $deleteProductValidation = $deleteProductValidation->validate($request);

            if (!$deleteProductValidation['success']) {
                return $this->crearRespuesta($deleteProductValidation);
            }

            $deleteProductResult = $deleteProductService->process($deleteProductValidation);
            $success = $deleteProductResult['success'];
            $title_response = $deleteProductResult['titleResponse'];
            $text_response = $deleteProductResult['textResponse'];
            $last_action = $deleteProductResult['lastAction'];
            $data = $deleteProductResult['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error delete product" . $exception->getMessage();
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

    public function catalogueProductElasticActiveInactive(Request $request, ToggleProductValidation $toggleProductValidation, ToggleProductService $toggleProductService)
    {
        try {
            if (!$toggleProductValidation->validate($request)) {
                return $this->crearRespuesta($toggleProductValidation->response);
            }
            $toggleProductResult = $toggleProductService->process($toggleProductValidation->response);
            $success = $toggleProductResult['success'];
            $title_response = $toggleProductResult['titleResponse'];
            $text_response = $toggleProductResult['textResponse'];
            $last_action = $toggleProductResult['lastAction'];
            $data = $toggleProductResult['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error activateInactivate product" . $exception->getMessage();
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

    public function topSellingProductsElastic(Request $request, ListProductValidation $topSellingProductValidation, TopSellingProductService $topSellingProductService)
    {
        try {
            if (!$topSellingProductValidation->validate($request)) {
                return $this->crearRespuesta($topSellingProductValidation->response);
            }
            $toggleProductResult = $topSellingProductService->process($topSellingProductValidation->response);
            $success = $toggleProductResult['success'];
            $title_response = $toggleProductResult['titleResponse'];
            $text_response = $toggleProductResult['textResponse'];
            $last_action = $toggleProductResult['lastAction'];
            $data = $toggleProductResult['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error topSelling product";
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
