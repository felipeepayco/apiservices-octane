<?php

namespace App\Http\Controllers\V2;

use App\Events\Catalogue\Process\CatalogueNewEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueActivateInactivateDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueApplyDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDeleteDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueActivateInactivateDiscountCodeEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDeleteDiscountCodeEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDiscountCodeEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueUpdateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Service\V2\Catalogue\Process\CatalogueChangeStatusService;
use App\Service\V2\Catalogue\Process\CatalogueCreateService;
use App\Service\V2\Catalogue\Process\CatalogueDeleteService;
use App\Service\V2\Catalogue\Process\CatalogueInactiveService;
use App\Service\V2\Catalogue\Process\CatalogueListService;
use App\Service\V2\Catalogue\Process\CatalogueReceiptService;
use App\Service\V2\Catalogue\Validations\CatalogueApplyDiscountCodeValidation;
use App\Service\V2\Catalogue\Validations\CatalogueChangeStatusValidation;
use App\Service\V2\Catalogue\Validations\CatalogueCreateValidation;
use App\Service\V2\Catalogue\Validations\CatalogueDeleteValidation;
use App\Service\V2\Catalogue\Validations\CatalogueInactiveValidation;
use App\Service\V2\Catalogue\Validations\CatalogueListValidation;
use App\Service\V2\Catalogue\Validations\CatalogueUpdateValidation;
use App\Service\V2\ShoppingCart\Process\GetShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiCatalogueControllerv2 extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function listCatalogue(Request $request, CatalogueListService $catalogueListService, CatalogueListValidation $catalogueListValidation)
    {

        try {
            if (!$catalogueListValidation->validate($request)) {
                return $this->crearRespuesta($catalogueListValidation->response);
            }

            $consultCatalogueList = $catalogueListService->process($catalogueListValidation->response);
            $success = $consultCatalogueList['success'];
            $title_response = $consultCatalogueList['titleResponse'];
            $text_response = $consultCatalogueList['textResponse'];
            $last_action = $consultCatalogueList['lastAction'];
            $data = $consultCatalogueList['data'];
        } catch (\Exception $exception) {
            Log::info($exception);
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

    public function catalogueNew(Request $request, CatalogueCreateService $catalogueCreateService, CatalogueUpdateValidation $catalogueUpdateValidation, CatalogueCreateValidation $catalogueCreateValidation)
    {
        try {
            $arr_parametros = $request->all();

            if (isset($arr_parametros["id"])) {
                if ($arr_parametros["id"]) {
                    $catalogueUpdateValidation = $catalogueUpdateValidation->validate2($request);

                    if (!$catalogueUpdateValidation["success"]) {
                        return $this->crearRespuesta($catalogueUpdateValidation);
                    }

                    $catalogueNew = $catalogueCreateService->process($catalogueUpdateValidation["data"]);
                } else {


                    $catalogueCreateValidation = $catalogueCreateValidation->validate2($request);
                    if (!$catalogueCreateValidation["success"]) {
                        return $this->crearRespuesta($catalogueCreateValidation);
                    }

                    $catalogueNew = $catalogueCreateService->process($catalogueCreateValidation["data"]);
                }

            } else {
                $catalogueCreateValidation = $catalogueCreateValidation->validate2($request);
                if (!$catalogueCreateValidation["success"]) {
                    return $this->crearRespuesta($catalogueCreateValidation);
                }

                $catalogueNew = $catalogueCreateService->process($catalogueCreateValidation["data"]);

            }

            $success = $catalogueNew['success'];
            $title_response = $catalogueNew['titleResponse'];
            $text_response = $catalogueNew['textResponse'];
            $last_action = $catalogueNew['lastAction'];
            $data = $catalogueNew['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
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

    public function catalogueChangeStatus(Request $request, CatalogueChangeStatusService $catalogueChangeStatusService, CatalogueChangeStatusValidation $catalogueChangeStatusValidation)
    {
        try {
            $arr_parametros = $request->all();
            $catalogueChangeStatusValidation = $catalogueChangeStatusValidation->validate2($request);

            if (!$catalogueChangeStatusValidation["success"]) {
                return $this->crearRespuesta($catalogueChangeStatusValidation);
            }

            $catalogueNew = $catalogueChangeStatusService->process($catalogueChangeStatusValidation["data"]);

            $success = $catalogueNew['success'];
            $title_response = $catalogueNew['titleResponse'];
            $text_response = $catalogueNew['textResponse'];
            $last_action = $catalogueNew['lastAction'];
            $data = $catalogueNew['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
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

    public function getCatalogueReceipt(Request $request, CatalogueReceiptService $catalogueReceiptService)
    {

        try {
            $catalogueReceipt = $catalogueReceiptService->process($request->all());

            $success = $catalogueReceipt['success'];
            $title_response = $catalogueReceipt['titleResponse'];
            $text_response = $catalogueReceipt['textResponse'];
            $last_action = $catalogueReceipt['lastAction'];
            $data = $catalogueReceipt['data'];
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

    public function catalogueDelete(Request $request, CatalogueDeleteService $catalogueDeleteService, CatalogueDeleteValidation $catalogueDeleteValidation)
    {
        try {
            $catalogueDeleteValidation = $catalogueDeleteValidation->validate($request);

            if (!$catalogueDeleteValidation["success"]) {

                return $this->crearRespuesta($catalogueDeleteValidation);
            }

            $catalogueDelete = $catalogueDeleteService->process($catalogueDeleteValidation["data"]);

            $success = $catalogueDelete['success'];
            $title_response = $catalogueDelete['titleResponse'];
            $text_response = $catalogueDelete['textResponse'];
            $last_action = $catalogueDelete['lastAction'];
            $data = $catalogueDelete['data'];
        } catch (\Exception $exception) {

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

    public function catalogueUpdate(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneralCatalogueNew = event(
                new ValidationGeneralCatalogueUpdateEvent($arr_parametros),
                $request
            );

            if (!$validationGeneralCatalogueNew[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueNew[0]);
            }

            $catalogueNew = event(
                new CatalogueNewEvent($validationGeneralCatalogueNew[0]),
                $request
            );

            $success = $catalogueNew[0]['success'];
            $title_response = $catalogueNew[0]['titleResponse'];
            $text_response = $catalogueNew[0]['textResponse'];
            $last_action = $catalogueNew[0]['lastAction'];
            $data = $catalogueNew[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
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

    public function catalogueInactive(Request $request, CatalogueInactiveService $catalogueInactiveService, CatalogueInactiveValidation $catalogueInactiveValidation)
    {
        try {

            if (!$catalogueInactiveValidation->validate($request)) {
                return $this->crearRespuesta($catalogueInactiveValidation->response);
            }

            $catalogueInactive = $catalogueInactiveService->process($catalogueInactiveValidation->response);

            $success = $catalogueInactive['success'];
            $title_response = $catalogueInactive['titleResponse'];
            $text_response = $catalogueInactive['textResponse'];
            $last_action = $catalogueInactive['lastAction'];
            $data = $catalogueInactive['data'];
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

    public function catalogueApplyDiscountCode(Request $request, CatalogueApplyDiscountCodeValidation $catalogueApplyDiscountCodeValidation, GetShoppingCartService $getShoppingCartService)
    {

        try {

            if (!$catalogueApplyDiscountCodeValidation->validate($request, $getShoppingCartService)) {
                return $this->crearRespuesta($catalogueApplyDiscountCodeValidation->response);
            }

            $catalogueDiscount = event(
                new CatalogueApplyDiscountCodeEvent($catalogueApplyDiscountCodeValidation->response),
                $request
            );

            $success = $catalogueDiscount[0]['success'];
            $title_response = $catalogueDiscount[0]['titleResponse'];
            $text_response = $catalogueDiscount[0]['textResponse'];
            $last_action = $catalogueDiscount[0]['lastAction'];
            $data = $catalogueDiscount[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error " . $exception->getFile();
            $text_response = "Error inesperado al consultar la informacion " . $exception->getMessage();
            $last_action = "NA " . $exception->getLine();
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

    public function catalogueDiscountCode(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();

            $validation_catalogue_discount_event = event(
                new ValidationCatalogueDiscountCodeEvent($arr_parametros),
                $request
            );

            if (!$validation_catalogue_discount_event[0]["success"]) {
                return $this->crearRespuesta($validation_catalogue_discount_event[0]);
            }

            $catalogueDiscount = event(
                new CatalogueDiscountCodeEvent($arr_parametros),
                $request
            );

            $success = $catalogueDiscount[0]['success'];
            $title_response = $catalogueDiscount[0]['titleResponse'];
            $text_response = $catalogueDiscount[0]['textResponse'];
            $last_action = $catalogueDiscount[0]['lastAction'];
            $data = $catalogueDiscount[0]['data'];
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

    public function catalogueDiscountCodeDelete(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();

            $validation_catalogue_discount_delete_event = event(
                new ValidationCatalogueDeleteDiscountCodeEvent($arr_parametros),
                $request
            );

            if (!$validation_catalogue_discount_delete_event[0]["success"]) {
                return $this->crearRespuesta($validation_catalogue_discount_delete_event[0]);
            }

            $catalogueDeleteDiscount = event(
                new CatalogueDeleteDiscountCodeEvent($arr_parametros),
                $request
            );

            $success = $catalogueDeleteDiscount[0]['success'];
            $title_response = $catalogueDeleteDiscount[0]['titleResponse'];
            $text_response = $catalogueDeleteDiscount[0]['textResponse'];
            $last_action = $catalogueDeleteDiscount[0]['lastAction'];
            $data = $catalogueDeleteDiscount[0]['data'];
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

    public function catalogueActivateInactivateDiscountCode(Request $request)
    {

        try {
            $arr_parametros = $request->request->all();

            $validation_catalogue_discount_event = event(
                new ValidationCatalogueActivateInactivateDiscountCodeEvent($arr_parametros),
                $request
            );

            if (!$validation_catalogue_discount_event[0]["success"]) {
                return $this->crearRespuesta($validation_catalogue_discount_event[0]);
            }

            $catalogueDiscount = event(
                new CatalogueActivateInactivateDiscountCodeEvent($arr_parametros),
                $request
            );

            $success = $catalogueDiscount[0]['success'];
            $title_response = $catalogueDiscount[0]['titleResponse'];
            $text_response = $catalogueDiscount[0]['textResponse'];
            $last_action = $catalogueDiscount[0]['lastAction'];
            $data = $catalogueDiscount[0]['data'];
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

    public function catalogueDiscountCodeList(Request $request)
    {

        try {
            $catalogueDiscount = event(
                new CatalogueDiscountCodeListEvent($request->request->all()),
                $request
            );

            $success = $catalogueDiscount[0]['success'];
            $titleResponse = $catalogueDiscount[0]['titleResponse'];
            $textResponse = $catalogueDiscount[0]['textResponse'];
            $lastAction = $catalogueDiscount[0]['lastAction'];
            $data = $catalogueDiscount[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $lastAction = "NA";
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
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => $lastAction,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }
}
