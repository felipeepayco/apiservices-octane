<?php

namespace App\Service\V2\Catalogue\Process;

use App\Helpers\Pago\HelperPago;
use App\Repositories\V2\CatalogueRepository;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;


class RebootCertificateService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function process($data)
    {
        try {
            $id = CommonValidation::getFieldValidation($data, "id", null);
            $clientId = CommonValidation::getFieldValidation($data, "clientId", null);
            $catalogueExistResult = $this->catalogueRepository->findByIdAndClientId($id, $clientId);
            if ($catalogueExistResult > 0) {
                $result = $this->catalogueRepository->rebootCertificate($id);
                if ($result) {
                    $success = true;
                    $title_response = 'Successful reboot certificate';
                    $text_response = 'successful reboot certificate';
                    $last_action = 'reboot certificate';
                    $data = [];
                }
            } else {
                $success = false;
                $title_response = 'Error reboot certificate catalogue';
                $text_response = 'Error reboot certificate catalogue, catalogue not found';
                $last_action = 'reboot certificate catalogue';
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado " . $exception->getMessage();
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' =>
                    $validate->errorMessage
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;

    }
}