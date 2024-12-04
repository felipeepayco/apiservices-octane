<?php

namespace App\Service\V2\Catalogue\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use Illuminate\Support\Facades\Log;

class CatalogueChangeStatusService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function process($data)
    {
        try {

            $fieldValidation = $data;
            $clientId = $fieldValidation[CommonText::CLIENTID];

            $id = $this->getFieldValidation($fieldValidation, "id");

            $active = $this->getFieldValidation($fieldValidation, CommonText::ACTIVE_ENG);

            $data = ["activo" => $active];

            $update = $this->catalogueRepository->update($id, $data);

            if ($update) {

                $catalogue = $this->catalogueRepository->find($id);
                $resp =[
                    "name"=>$catalogue->nombre,
                    "active"=>$catalogue->activo,
                    "progress"=>$catalogue->progreso
                ];

                $success = true;
                $title_response = "Successful updated catalogue";
                $text_response = "successful updated catalogue";
                $last_action = "catalogue_update";
                $data = $resp;
            } else {
                $success = false;
                $title_response = "The record couldn't be updated";
                $text_response = "The record couldn't be updated";
                $last_action = "catalogue_update}";
                $data = [];
            }

            $this->deleteCatalogueRedis($id);

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error changing state catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);

            Log::info($exception);

        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;

            Log::info($exception);

        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;
    }

    private function deleteCatalogueRedis($id)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $id);
        if ($exist) {
            $redis->del('vende_catalogue_' . $id);
        }
    }

}
