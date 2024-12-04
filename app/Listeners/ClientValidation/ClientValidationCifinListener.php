<?php

namespace App\Listeners\ClientValidation;

use App\Events\ClientValidation\ClientValidationCifinEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;


class ClientValidationCifinListener extends HelperPago
{

	/**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ClientValidationCifinEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;

            $clientId = $fieldValidation["clientId"];
            $docType=$fieldValidation["docType"];
            $docNumber=$fieldValidation["docNumber"];

            $validate = new Validate();
            $arr_respuesta=array();

            if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'cliente Id');
            if (!$vclientId) {
                $validate->setError(500, "field clienteId is required");
            } else {
                $arr_respuesta['clienteId'] = $clientId;
            }
            } else {
                $validate->setError(500, "field clientId is required");
            }

            if (isset($docType)) {
            $vdocType = $validate->ValidateVacio($docType, 'docType');
            if (!$vdocType) {
                $validate->setError(500, "field docType is required");
            } else {
                $arr_respuesta['docType'] = $docType;
            }
            } else {
                $validate->setError(500, "field docType is required");
            }

            if (isset($docNumber)) {
            $vdocNumber = $validate->ValidateVacio($docNumber, 'docNumber');
            if (!$vdocNumber) {
                $validate->setError(500, "field docNumber is required");
            } else {
                $arr_respuesta['docNumber'] = $docNumber;
            }
            } else {
                $validate->setError(500, "field docNumber is required");
            }

            if($validate->totalerrors>0){
                $success=false;
                $title_response = 'Error';
                $text_response = "Error invalid params";
                $last_action = 'validate inputs';
                $data = $validate;
            }else{

                $success = true;
                $title_response = 'Success token generate';
                $text_response = "Success token generate";
                $last_action = 'validate inputs';
                $data = $arr_respuesta;
            }

           


        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error validate data";
            $last_action = 'Internal server error';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }


        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $title_response;
        $arrResponse['textResponse'] = $text_response;
        $arrResponse['lastAction'] = $last_action;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

}