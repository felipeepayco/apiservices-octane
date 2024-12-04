<?php

namespace App\Http\Controllers;

use App\Events\Catalogue\Process\CatalogueDeleteEvent;
use App\Events\Catalogue\Process\CatalogueInactiveEvent;
use App\Events\Catalogue\Process\CatalogueNewEvent;
use App\Events\Catalogue\Process\ConsultCatalogueListEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueActivateInactivateDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueApplyDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDeleteDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeEvent;
use App\Events\Catalogue\Process\DiscountCode\CatalogueDiscountCodeListEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueActivateInactivateDiscountCodeEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueApplyDiscountCodeEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDeleteDiscountCodeEvent;
use App\Events\Catalogue\Validation\DiscountCode\ValidationCatalogueDiscountCodeEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueDeleteEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueInactiveEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueListEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueNewEvent;
use App\Events\Catalogue\Validation\ValidationGeneralCatalogueUpdateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiCatalogueControllerv2 extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function listCatalogue(Request $request)
    {

        try {


            $arr_parametros = $request->request->all();
            $arr_parametros["page"] = $request->get("page");
            $validationGeneralCatalogueList = event(
                new ValidationGeneralCatalogueListEvent($arr_parametros),
                $request);
            if (!$validationGeneralCatalogueList[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueList[0]);
            }

            $consultCatalogueList = event(
                new ConsultCatalogueListEvent($validationGeneralCatalogueList[0]),
                $request
            );

            $success = $consultCatalogueList[0]['success'];
            $title_response = $consultCatalogueList[0]['titleResponse'];
            $text_response = $consultCatalogueList[0]['textResponse'];
            $last_action = $consultCatalogueList[0]['lastAction'];
            $data = $consultCatalogueList[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function catalogueNew(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();

            if ($arr_parametros["id"]) {

                $validationGeneralCatalogueNew = event(
                    new ValidationGeneralCatalogueUpdateEvent($arr_parametros),
                    $request);

            } else {

                $validationGeneralCatalogueNew = event(
                    new ValidationGeneralCatalogueNewEvent($arr_parametros),
                    $request);
            }

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
            $error = $this->getErrorCheckout('AE100');
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

    public function catalogueDelete(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralCatalogueDelete = event(
                new ValidationGeneralCatalogueDeleteEvent($arr_parametros),
                $request);
            if (!$validationGeneralCatalogueDelete[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueDelete[0]);
            }

            $catalogueDelete = event(
                new CatalogueDeleteEvent($validationGeneralCatalogueDelete[0]),
                $request
            );

            $success = $catalogueDelete[0]['success'];
            $title_response = $catalogueDelete[0]['titleResponse'];
            $text_response = $catalogueDelete[0]['textResponse'];
            $last_action = $catalogueDelete[0]['lastAction'];
            $data = $catalogueDelete[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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
                $request);

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
            $error = $this->getErrorCheckout('AE100');
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

    public function catalogueInactive(Request $request)
    {
        try {


            $arr_parametros = $request->request->all();
            $validationGeneralCatalogueInactive = event(
                new ValidationGeneralCatalogueInactiveEvent($arr_parametros),
                $request);

            if (!$validationGeneralCatalogueInactive[0]["success"]) {
                return $this->crearRespuesta($validationGeneralCatalogueInactive[0]);
            }

            $catalogueInactive = event(
                new CatalogueInactiveEvent($validationGeneralCatalogueInactive[0]),
                $request
            );

            $success = $catalogueInactive[0]['success'];
            $title_response = $catalogueInactive[0]['titleResponse'];
            $text_response = $catalogueInactive[0]['textResponse'];
            $last_action = $catalogueInactive[0]['lastAction'];
            $data = $catalogueInactive[0]['data'];

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
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

    public function catalogueApplyDiscountCode(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();

            $validation_catalogue_discount_event = event(
                new ValidationCatalogueApplyDiscountCodeEvent($arr_parametros),
                $request);

            if (!$validation_catalogue_discount_event[0]["success"]) {
                return $this->crearRespuesta($validation_catalogue_discount_event[0]);
            }

             $catalogueDiscount = event(
                new CatalogueApplyDiscountCodeEvent($validation_catalogue_discount_event[0]["data"]),
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
            $error = $this->getErrorCheckout('AE100');
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
                $request);

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
            $error = $this->getErrorCheckout('AE100');
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
                $request);

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
            $error = $this->getErrorCheckout('AE100');
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
                $request);

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
            $error = $this->getErrorCheckout('AE100');
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
            $error = $this->getErrorCheckout('AE100');
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