<?php
namespace App\Http\Controllers;

use App\Events\ConsultSellDeleteEvent;
use App\Events\ConsultSellEditEvent;
use App\Events\CatalogueProductNewEvent;

//elastic
use App\Events\CatalogueProductNewElasticEvent;
use App\Events\ConsultCatalogueProductListElasticEvent;
use App\Events\ConsultCatalogueTopSellingProductsListElasticEvent;
use App\Events\ValidationGeneralCatalogueProductDeleteElasticEvent;
use App\Events\CatalogueProductDeleteElasticEvent;
//////
use App\Events\ValidationGeneralCatalogueProductListElasticEvent;
use App\Events\ValidationGeneralCatalogueProductNewElasticEvent;

use App\Events\ValidationGeneralCatalogueProductListEvent;
use App\Events\ConsultCatalogueProductListEvent;
use App\Events\ConsultCatalogueTopSellingProductsListEvent;
use App\Events\ValidationGeneralCatalogueProductDeleteEvent;
use App\Events\ValidationGeneralCatalogueProductActiveInactiveElasticEvent;
use App\Events\CatalogueProductDeleteEvent;
use App\Events\CatalogueProductActiveInactiveElasticEvent;


use App\Events\ValidationGeneralCatalogueProductNewEvent;

use App\Events\ValidationGeneralSellDeleteEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCatalogueProductController extends HelperPago {

    public function __construct(Request $request) {
        parent::__construct($request);

    }

    public function listproducts(Request $request) {

        try{

            $arr_parametros=$request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralCatalogueProductListEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellList[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList=event(
                new ConsultCatalogueProductListEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
            
        }catch (Exception $exception){
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

    // v2 elastic

    public function listproductsElastic(Request $request) {

        try{

            $arr_parametros=$request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralCatalogueProductListElasticEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellList[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList=event(
                new ConsultCatalogueProductListElasticEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $body = $consultSellList[0]['data'];
            $data = $body["data"];
            unset($body["data"]);
            $paginateInfo = $body;
            
            
        }catch (Exception $exception){
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
            'data' => $data,
            'paginate_info'=>$paginateInfo ?? "",
        );
        return $this->crearRespuesta($response);
    }
    public function catalogueProductNew(Request $request){
        try{

            $arr_parametros=$request->request->all();
        
            $validationGeneralSellNew = event(
                new ValidationGeneralCatalogueProductNewEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellNew[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellNew[0]);
            }

            $consultSellNew=event(
                new CatalogueProductNewEvent($validationGeneralSellNew[0]),
                $request
            );

            

            $success = $consultSellNew[0]['success'];
            $title_response = $consultSellNew[0]['titleResponse'];
            $text_response = $consultSellNew[0]['textResponse'];
            $last_action = $consultSellNew[0]['lastAction'];
            $data = $consultSellNew[0]['data'];

        }catch (Exception $exception){
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

    //elastic
    public function catalogueProductNewElastic(Request $request){
        try{
            $arr_parametros=$request->request->all();
            
            $validationGeneralSellNew = event(
                new ValidationGeneralCatalogueProductNewElasticEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellNew[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellNew[0]);
            }

            $consultSellNew=event(
                new CatalogueProductNewElasticEvent($validationGeneralSellNew[0]),
                $request
            );

            $success = $consultSellNew[0]['success'];
            $title_response = $consultSellNew[0]['titleResponse'];
            $text_response = $consultSellNew[0]['textResponse'];
            $last_action = $consultSellNew[0]['lastAction'];
            $data = $consultSellNew[0]['data'];

        }catch (Exception $exception){
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

    public function catalogueProductDelete(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellDelete = event(
                new ValidationGeneralCatalogueProductDeleteEvent($arr_parametros),
                $request);
            if(!$validationGeneralSellDelete[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellDelete[0]);
            }

            $consultSellDelete=event(
                new CatalogueProductDeleteEvent($validationGeneralSellDelete[0]),
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
            $text_response = "Error delete product" . $exception->getMessage();
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
    
    
    //elastic
    public function catalogueProductElasticDelete(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellDelete = event(
                new ValidationGeneralCatalogueProductDeleteElasticEvent($arr_parametros),
                $request);
            if(!$validationGeneralSellDelete[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellDelete[0]);
            }

            $consultSellDelete=event(
                new CatalogueProductDeleteElasticEvent($validationGeneralSellDelete[0]),
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
            $text_response = "Error delete product" . $exception->getMessage();
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
    //elastic
    public function catalogueProductElasticActiveInactive(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellDelete = event(
                new ValidationGeneralCatalogueProductActiveInactiveElasticEvent($arr_parametros),
                $request);
            if(!$validationGeneralSellDelete[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellDelete[0]);
            }

            $consult=event(
                new CatalogueProductActiveInactiveElasticEvent($validationGeneralSellDelete[0]),
                $request
            );

            $success = $consult[0]['success'];
            $title_response = $consult[0]['titleResponse'];
            $text_response = $consult[0]['textResponse'];
            $last_action = $consult[0]['lastAction'];
            $data = $consult[0]['data'];


        }catch (\Exception $exception){
            $success = false;
            $title_response = "Error";
            $text_response = "Error activateInactivate product" . $exception->getMessage();
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
    public function catalogueProductEdit(Request $request){
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellEdit = event(
                new ValidationGeneralSellDeleteEvent($arr_parametros),
                $request);
            if(!$validationGeneralSellEdit[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellEdit[0]);
            }

            $consultSellEdit=event(
                new ConsultSellEditEvent($validationGeneralSellEdit[0]),
                $request
            );

            $success =$consultSellEdit[0]['success'];
            $title_response =$consultSellEdit[0]['titleResponse'];
            $text_response =$consultSellEdit[0]['textResponse'];
            $last_action =$consultSellEdit[0]['lastAction'];
            $data =$consultSellEdit[0]['data'];
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

    public function catalogueProductUpdate(Request $request){

        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellUpdate= event(new ValidationGeneralCatalogueProductNewEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellUpdate[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellUpdate[0]);
            }

            $consultSellUpdate=event(
                new CatalogueProductNewEvent($validationGeneralSellUpdate[0]),
                $request
            );

            $success =$consultSellUpdate[0]['success'];
            $title_response =$consultSellUpdate[0]['titleResponse'];
            $text_response =$consultSellUpdate[0]['textResponse'];
            $last_action =$consultSellUpdate[0]['lastAction'];
            $data =$consultSellUpdate[0]['data'];

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

    public function catalogueProductUpdateElastic(Request $request){

        try{
            $arr_parametros=$request->request->all();
            $validationGeneralSellUpdate= event(new ValidationGeneralCatalogueProductNewElasticEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellUpdate[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellUpdate[0]);
            }

            $consultSellUpdate=event(
                new CatalogueProductNewElasticEvent($validationGeneralSellUpdate[0]),
                $request
            );

            $success =$consultSellUpdate[0]['success'];
            $title_response =$consultSellUpdate[0]['titleResponse'];
            $text_response =$consultSellUpdate[0]['textResponse'];
            $last_action =$consultSellUpdate[0]['lastAction'];
            $data =$consultSellUpdate[0]['data'];

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

        public function topSellingProducts(Request $request) {

        try{

            $arr_parametros=$request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralCatalogueProductListEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellList[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList=event(
                new ConsultCatalogueTopSellingProductsListEvent($validationGeneralSellList[0]),
                $request
            );

            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
            
        }catch (Exception $exception){
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('E100');
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

    public function topSellingProductsElastic(Request $request) {

        try{

            $arr_parametros=$request->request->all();
            $validationGeneralSellList = event(
                new ValidationGeneralCatalogueProductListElasticEvent($arr_parametros),
                $request);

            if(!$validationGeneralSellList[0]["success"]){
                return $this->crearRespuesta($validationGeneralSellList[0]);
            }

            $consultSellList=event(
                new ConsultCatalogueTopSellingProductsListElasticEvent($validationGeneralSellList[0]),
                $request
            );
            $success = $consultSellList[0]['success'];
            $title_response = $consultSellList[0]['titleResponse'];
            $text_response = $consultSellList[0]['textResponse'];
            $last_action = $consultSellList[0]['lastAction'];
            $data = $consultSellList[0]['data'];
            
        }catch (Exception $exception){
            $success = false;
            $title_response = "Error";
            $text_response = "Error query database" . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('E100');
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
