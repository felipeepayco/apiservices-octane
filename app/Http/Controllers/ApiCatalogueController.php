<?php

namespace App\Http\Controllers;

use App\Events\CatalogueDeleteEvent;
use App\Events\CatalogueNewEvent;
use App\Events\ConsultCatalogueListEvent;
use App\Events\ConsultSellEditEvent;

use App\Events\ValidationGeneralCatalogueDeleteEvent;
use App\Events\ValidationGeneralCatalogueListEvent;


use App\Events\ValidationGeneralCatalogueNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCatalogueController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function listCatalogue(Request $request)
    {

        try {

            $arr_parametros = $request->request->all();
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
                'errores' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data

        );
        return $this->crearRespuesta($response);
    }

    public function catalogueNew(Request $request)
    {
        try {

            $arr_parametros = $request->request->all();
            $validationGeneralCatalogueNew = event(
                new ValidationGeneralCatalogueNewEvent($arr_parametros),
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
                'errors' => $validate->errorMessage
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
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
                'errores' => $validate->errorMessage
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }
}