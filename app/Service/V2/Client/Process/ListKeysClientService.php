<?php

namespace App\Service\V2\Client\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ClientGatewayRepository;
use Illuminate\Http\Request;

class ListKeysClientService extends HelperPago
{
    private $client_gateway_repository;

    public function __construct(
        Request $request,
        ClientGatewayRepository $client_gateway_repository,

    ) {
        parent::__construct($request);
        $this->client_gateway_repository = $client_gateway_repository;

    }

    public function handle($params)
    {
        try {

            $clientId = $params["idClient"];

            $filters =
                [
                "cliente_id" => $clientId,
            ];

            $clientKeys = $this->client_gateway_repository->findByCriteria($filters);

            if (isset($params["nameCatalogue"])) {
                $nombre = $params["nameCatalogue"];
                $clientKeys['nameCatalogue'] = $nombre;
            }
            $success = true;
            $title_response = 'Successfully consult client keys';
            $text_response = 'Successfully consult client keys';
            $last_action = 'consult_list_catalogue';
            $data = $clientKeys;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
            $last_action = 'consult client keys';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}
