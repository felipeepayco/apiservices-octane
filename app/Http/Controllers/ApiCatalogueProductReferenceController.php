<?php
namespace App\Http\Controllers;


use App\Events\ConsultCatalogueProductReferenceCreateEvent;
use App\Events\ValidationGeneralCatalogueProductReferenceCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiCatalogueProductReferenceController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function catalogueProductReferenceCreate(Request $request)
    {
        try{
            $arr_parametros=$request->request->all();
            $validationGeneralCatalogueProductReference = event(
                new ValidationGeneralCatalogueProductReferenceCreateEvent($arr_parametros),
                $request);

            if(!$validationGeneralCatalogueProductReference[0]["success"]){
                return $this->crearRespuesta($validationGeneralCatalogueProductReference[0]);
            }

            $consulCatalogueProductReferenceProduct=event(
                new ConsultCatalogueProductReferenceCreateEvent($validationGeneralCatalogueProductReference[0]),
                $request
            );

            $success = $consulCatalogueProductReferenceProduct[0]['success'];
            $title_response = $consulCatalogueProductReferenceProduct[0]['titleResponse'];
            $text_response = $consulCatalogueProductReferenceProduct[0]['textResponse'];
            $last_action = $consulCatalogueProductReferenceProduct[0]['lastAction'];
            $data = $consulCatalogueProductReferenceProduct[0]['data'];

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
}