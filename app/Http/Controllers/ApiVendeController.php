<?php

namespace App\Http\Controllers;

use App\Events\Catalogue\Process\Plans\DowngradePlanEvent;
use App\Events\Catalogue\Validation\Plans\ValidationDowngradePlanEvent;
use App\Events\Vende\Process\ProcessConfigurationBabiloniaEvent;
use App\Events\Vende\Process\ProcessShowConfigurationCatalogueEvent;
use App\Events\Vende\Process\ProcessVendePlanEvent;
use App\Events\Vende\Validation\ValidationShowConfigurationCatalogueEvent;
use App\Helpers\Pago\HelperPago;
use Illuminate\Http\Request;

class ApiVendeController extends HelperPago
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function test()
    {
        return "v1".app('api.version');
    }
    public function planRestrictionUpdate(Request $request)
    {
        $arr_parametros = $request->request->all();

        $validationDowngradePlanListener = event(
            new ValidationDowngradePlanEvent($arr_parametros),
            $request);

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

    public function showConfigurationCatalogue(Request $request)
    {

        $arr_parametros = $request->request->all();

        $validationShowConfigurationCatalogueListener = event(
            new ValidationShowConfigurationCatalogueEvent($arr_parametros),
            $request);
        if (!$validationShowConfigurationCatalogueListener[0]["success"]) {
            return $this->crearRespuesta($validationShowConfigurationCatalogueListener[0]);
        }

        $showConfigurationCatalogueListener = event(
            new ProcessShowConfigurationCatalogueEvent($arr_parametros),
            $request
        );

        $success = $showConfigurationCatalogueListener[0]['success'];
        $title_response = $showConfigurationCatalogueListener[0]['titleResponse'];
        $text_response = $showConfigurationCatalogueListener[0]['textResponse'];
        $last_action = $showConfigurationCatalogueListener[0]['lastAction'];
        $data = $showConfigurationCatalogueListener[0]['data'];

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

        \Log::info(app('api.version'));
        \Log::info("aca v1");
        $arr_parametros = $request->request->all();
        $configuration = event(
            new ProcessConfigurationBabiloniaEvent($arr_parametros),
            $request
        );

        $success = $configuration[0]['success'];
        $title_response = $configuration[0]['titleResponse'];
        $text_response = $configuration[0]['textResponse'];
        $last_action = $configuration[0]['lastAction'];
        $data = $configuration[0]['data'];

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

}
