<?php

namespace App\Service\V2\Catalogue\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;

class CatalogueReceiptService extends HelperPago
{

    protected $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;

    }
    public function process($data)
    {
        try {
            $fieldData = $data;
            $success = false;
            $title_response = "";
            $text_response = "";
            $data = [];
            $last_action = "fetch data from database";

            $response = $this->catalogueRepository->find($fieldData["id"]);

            if (!empty($response)) {
                $success = true;
                $title_response = $response["message"];
                $text_response = $response["message"];
                $data = $response;
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error generating receipt";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
