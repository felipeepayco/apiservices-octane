<?php
namespace App\Http\Controllers;

use App\Events\ConsultCatalogueCategoriesDeleteEvent;
use App\Events\ConsultCatalogueCategoriesEditEvent;
use App\Events\ConsultCatalogueCategoriesListEvent;
use App\Events\ConsultCatalogueCategoriesNewEvent;
use App\Events\ConsultCatalogueCategoriesUpdateEvent;
use App\Events\ValidationGeneralCatalogueCategoriesDeleteEvent;
use App\Events\ValidationGeneralCatalogueCategoriesListEvent;
use App\Events\ValidationGeneralCatalogueCategoriesNewEvent;
use App\Events\ValidationGeneralCatalogueCategoriesUpdateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCatalogueCategoriesController extends HelperPago {

    public function __construct(Request $request) {
        parent::__construct($request);

    }

    public function catalogueCategoriesList(Request $request) {
        try {
            $arr_parametros=$request->request->all();
            $validationGeneralCatalogueCategoryList = event(
                new ValidationGeneralCatalogueCategoriesListEvent($arr_parametros),
                $request);

            if(!$validationGeneralCatalogueCategoryList[0]["success"]){
                return $this->crearRespuesta($validationGeneralCatalogueCategoryList[0]);
            }

            $consulCatalogueCategory=event(
                new ConsultCatalogueCategoriesListEvent($validationGeneralCatalogueCategoryList[0]),
                $request
            );

            $success = $consulCatalogueCategory[0]['success'];
            $title_response = $consulCatalogueCategory[0]['titleResponse'];
            $text_response = $consulCatalogueCategory[0]['textResponse'];
            $last_action = $consulCatalogueCategory[0]['lastAction'];
            $data = $consulCatalogueCategory[0]['data'];

        }catch (\Exception $exception){
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

    public function catalogueCategoriesNew(Request $request) {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralCatalogueCategoriesNewEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellList[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList=event(
                new ConsultCatalogueCategoriesNewEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
        }catch (\Exception $exception){
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

    public function catalogueCategoriesDelete(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralCatalogueCategoriesDelete = event(
                new ValidationGeneralCatalogueCategoriesDeleteEvent($arr_parametros),
                $request);
            if(!$validationGeneralCatalogueCategoriesDelete[0]["success"]){
                return $this->crearRespuesta($validationGeneralCatalogueCategoriesDelete[0]);
            }

            $consultSellDelete=event(
                new ConsultCatalogueCategoriesDeleteEvent($validationGeneralCatalogueCategoriesDelete[0]),
                $request
            );

            $success = $consultSellDelete[0]['success'];
            $title_response = $consultSellDelete[0]['titleResponse'];
            $text_response = $consultSellDelete[0]['textResponse'];
            $last_action = $consultSellDelete[0]['lastAction'];
            $data = $consultSellDelete[0]['data'];


        }catch (\Exception $exception){
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

    public function catalogueCategoriesEdit(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralCatalogueCategoriesEdit = event(
                new ValidationGeneralCatalogueCategoriesDeleteEvent($arr_parametros),
                $request);
            if(!$validationGeneralCatalogueCategoriesEdit[0]["success"]){
                return $this->crearRespuesta($validationGeneralCatalogueCategoriesEdit[0]);
            }

            $consultCatalogueCategoriesEdit=event(
                new ConsultCatalogueCategoriesEditEvent($validationGeneralCatalogueCategoriesEdit[0]),
                $request
            );

            $success =$consultCatalogueCategoriesEdit[0]['success'];
            $title_response =$consultCatalogueCategoriesEdit[0]['titleResponse'];
            $text_response =$consultCatalogueCategoriesEdit[0]['textResponse'];
            $last_action =$consultCatalogueCategoriesEdit[0]['lastAction'];
            $data =$consultCatalogueCategoriesEdit[0]['data'];
        }catch (\Exception $exception){
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

    public function catalogueCategoriesUpdate(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralCatalogueCategoriesUpdate = event(
                new ValidationGeneralCatalogueCategoriesUpdateEvent($arr_parametros),
                $request);
            if(!$validationGeneralCatalogueCategoriesUpdate[0]["success"]){
                return $this->crearRespuesta($validationGeneralCatalogueCategoriesUpdate[0]);
            }

            $consultCatalogueCategoriesUpdate=event(
                new ConsultCatalogueCategoriesUpdateEvent($validationGeneralCatalogueCategoriesUpdate[0]),
                $request
            );

            $success =$consultCatalogueCategoriesUpdate[0]['success'];
            $title_response =$consultCatalogueCategoriesUpdate[0]['titleResponse'];
            $text_response =$consultCatalogueCategoriesUpdate[0]['textResponse'];
            $last_action =$consultCatalogueCategoriesUpdate[0]['lastAction'];
            $data =$consultCatalogueCategoriesUpdate[0]['data'];
        }catch (\Exception $exception){
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