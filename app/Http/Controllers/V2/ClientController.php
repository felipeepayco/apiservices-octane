<?php

namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Service\V2\Client\Process\ListKeysClientService;
use App\Service\V2\Client\Validations\ListKeysClientValidation;
use Illuminate\Http\Request;

class ClientController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function listKeysClient(Request $request, ListKeysClientService $list_keys_client_service, ListKeysClientValidation $list_keys_client_validation)
    {
        try {

            $arrParams = $request->request->all();
            $validationGeneral = $list_keys_client_validation->handle($request);

            if (!$validationGeneral["success"]) {
                return $this->crearRespuesta($validationGeneral);
            }

            // si data son los datos el catalogos o son los datos del cliente bbl
            $arrParams["idClient"] = isset($validationGeneral["data"]["cliente_id"]) ? $validationGeneral["data"]["cliente_id"] : $validationGeneral["data"]["id"];
            $arrParams["nameCatalogue"] = isset($validationGeneral["data"]["cliente_id"]) ? $validationGeneral["data"]["nombre"] : null;

            $consult = $list_keys_client_service->handle($arrParams);

            $success = $consult['success'];
            $title_response = $consult['titleResponse'];
            $text_response = $consult['textResponse'];
            $last_action = $consult['lastAction'];
            $data = $consult['data'];
        } catch (\Exception $exeption) {
            $success = $exeption->getMessage();
            $title_response = $exeption->getMessage();
            $text_response = $exeption->getMessage();
            $last_action = "listKeysClients";
            $data = [];
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

}
