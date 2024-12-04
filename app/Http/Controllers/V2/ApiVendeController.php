<?php

namespace App\Http\Controllers\V2;

use App\Events\Catalogue\Process\Plans\DowngradePlanEvent;
use App\Events\Catalogue\Validation\Plans\ValidationDowngradePlanEvent;
use App\Events\Vende\Process\ProcessVendePlanEvent;
use App\Helpers\Pago\HelperPago;
use App\Service\V2\Catalogue\Process\CreatedCertificateService;
use App\Service\V2\Catalogue\Process\RebootCertificateService;
use App\Service\V2\Catalogue\Process\ShowConfigurationCatalogueService;
use App\Service\V2\Catalogue\Validations\CreatedCertificateValidation;
use App\Service\V2\Catalogue\Validations\ShowConfigurationCatalogueValidation;
use App\Service\V2\Catalogue\Validations\RebootCertificateValidation;
use App\Service\V2\Catalogue\Process\CatalogueQueryDomainWithoutCertificates;
use App\Service\V2\Configuration\Process\ConfigurationService;
use App\Service\V2\Configuration\Process\ProcessConsultationCNAME;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiVendeController extends HelperPago
{
    private $configurationService;
   /*
    public function __construct(Request $request, ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
        parent::__construct($request);
    }*/


    public function test()
    {
        return "v2".app('api.version');
    }

    public function planRestrictionUpdate(Request $request)
    {
        $arr_parametros = $request->request->all();

        $validationDowngradePlanListener = event(
            new ValidationDowngradePlanEvent($arr_parametros),
            $request
        );

        if (!$validationDowngradePlanListener[0]["success"]) {
            return $this->crearRespuesta($validationDowngradePlanListener[0]);
        }

        $downgradePlanListener = event(
            new DowngradePlanEvent($arr_parametros),
            $request
        );

        $success = $downgradePlanListener[0]['success'];
        $title_response = $downgradePlanListener[0]['titleResponse'];
        $text_response = $downgradePlanListener[0]['textResponse'];
        $last_action = $downgradePlanListener[0]['lastAction'];
        $data = $downgradePlanListener[0]['data'];

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

    public function showConfigurationCatalogue(Request $request, ShowConfigurationCatalogueService $show_catalogue_configuration_service)
    {

        $show_catalogue_configuration_validation = new ShowConfigurationCatalogueValidation($request);
        $validationGeneral = $show_catalogue_configuration_validation->handle($request);

        if (!$validationGeneral["success"]) {
            return $this->crearRespuesta($validationGeneral);
        }

        $consult = $show_catalogue_configuration_service->handle($validationGeneral);
        $success = $consult['success'];
        $title_response = $consult['titleResponse'];
        $text_response = $consult['textResponse'];
        $last_action = $consult['lastAction'];
        $data = $consult['data'];

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

    public function configurationBabilonia(Request $request)
    {
        \Log::info("aca v2");
        $arr_parametros = $request->request->all();
        $configuration = $this->configurationService->process($arr_parametros);

        $success = $configuration['success'];
        $title_response = $configuration['titleResponse'];
        $text_response = $configuration['textResponse'];
        $last_action = $configuration['lastAction'];
        $data = $configuration['data'];

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

    public function getPlanByProduct(Request $request)
    {
        $arr_parametros = $request->request->all();

        $vende = event(
            new ProcessVendePlanEvent($arr_parametros),
            $request
        );

        $response = array(
            'code' => $vende[0]['code'],
            'data' => $vende[0]['data'],
            'message' => $vende[0]['message'],
            'paginate_info' => $vende[0]['paginate_info'],
            'status' => $vende[0]['status'],

        );
        return $this->crearRespuesta($response);
    }

    public function queryCname(Request $request, ProcessConsultationCNAME $processConsultationCNAME)
    {
        $arr_parametros = $request->request->all();
        $reponse = $processConsultationCNAME->process($arr_parametros);
        $response = array(
            'lastAction' => $reponse['lastAction'],
            'textResponse' => $reponse['textResponse'],
            'data' => $reponse['data'],
            'message' => $reponse['textResponse'],
            'success' => $reponse['success'],

        );
        return $this->crearRespuesta($response);
    }

    public function withoutCertificates(Request $request, CatalogueQueryDomainWithoutCertificates $catalogueQueryDomainWithoutCertificates)
    {
        try{
            $arr_parametros = $request->request->all();
            $result = $catalogueQueryDomainWithoutCertificates->process($arr_parametros);
            return $result;
        }catch(\Exception $e){
            return "NA";   
        }
    }
    
    public function createdCertificate(Request $request, CreatedCertificateService $createdCertificateService, CreatedCertificateValidation $validation)
    {
        try{
    
            if (!$validation->validate($request)) {
                return $this->responseSpeed($validation->response);
            }
    
            $createdCertificateService->process($validation->response);
            $success = true;
            $title_response = "Certificado creado";
            $text_response = "Certificado creado correctamente"; 
        }catch(\Exception $e){
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al crear certtificado" . $e->getMessage();      
        }
        $out=compact("success","title_response","text_response");
        return $this->crearRespuesta($out);
    
    }

    public function rebootCertificates(Request $request, RebootCertificateService $rebootCertificateService, RebootCertificateValidation $validation)
    {
        try{
            $validationGeneral = $validation->validate($request);
            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }
    
            $data = $rebootCertificateService->process($validationGeneral);
            return $this->crearRespuesta($data);
        }catch(\Exception $e){
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error inesperado al reiniciar el proceso de certtificación" . $e->getMessage();      
            $out=compact("success","titleResponse","textResponse");
            return $this->crearRespuesta($out);
        }
    
    }
}
