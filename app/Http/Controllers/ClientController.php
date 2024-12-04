<?php

namespace App\Http\Controllers;

use App\Events\ConsultClientListKeysEvent;
use App\Events\ValidationGeneralClientListKeysEvent;
use App\Helpers\Pago\HelperPago;
use App\Models\Clientes;
use App\Models\DetalleConfClientes;
use App\Models\LlavesClientes;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function bearerToken()
    {
        $header = $this->request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }

    /**
     * @param $request
     * @param $autController AuthController
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     *
     */
    public function auth($request, $autController)
    {
        $token = $this->bearerToken();
        try {
            $decoded = JWT::decode($token, env('JWT_SECRET'), array('HS256'));
            $decoded_array = (array) $decoded;
            $clienteId = $decoded_array['sub'];
            $expired = $decoded_array['exp'];

            if (strtotime(date('Y-m-d H:i:s')) > $expired) {
                $token = "";
            }
            $entityClientId = $autController->getHeaderEntityClienteId();
            if ($autController->getHeaderEntityClienteId()) {
                $clienteId = (int) $entityClientId;
            }

            $request->request->add(['clientId' => $clienteId]);
            $user = LlavesClientes::where('cliente_id', $clienteId)->first();
            if ($user) {
                $request->request->add(['public_key' => $user->public_key]);
                $request->request->add(['private_key' => $user->private_key]);
            }

            if ($clienteId) {
                $cliente = Clientes::find($clienteId);
                if ($cliente) {
                    $detailConfClient = DetalleConfClientes::where("cliente_id", $clienteId)
                        ->where("config_id", 16)
                        ->first();
                    app('translator')->setLocale(strtolower($detailConfClient ? $detailConfClient->valor : "ES"));
                } else {
                    $token = "";
                }
            }
        } catch (\Exception $ex) {
            $token = "";
        }

        return $token;
    }

    public function listKeysClient(Request $request)
    {
        try {
            $arrParams = $request->request->all();

            $validationGeneralClientListKeys = event(
                new ValidationGeneralClientListKeysEvent($arrParams),
                $request
            );

            if (!$validationGeneralClientListKeys[0]["success"]) {
                return $this->crearRespuesta($validationGeneralClientListKeys[0]);
            }
            // si data son los datos el catalogos o son los datos del cliente bbl
            $arrParams["idClient"] = isset($validationGeneralClientListKeys[0]["data"]->cliente_id) ? $validationGeneralClientListKeys[0]["data"]->cliente_id: $validationGeneralClientListKeys[0]["data"]->id;
            $arrParams["nameCatalogue"] = isset($validationGeneralClientListKeys[0]["data"]->cliente_id) ? $validationGeneralClientListKeys[0]["data"]->nombre : null;

            $consultClientListKeys = event(
                new ConsultClientListKeysEvent($arrParams),
                $request
            );

            $success = $consultClientListKeys[0]['success'];
            $title_response = $consultClientListKeys[0]['titleResponse'];
            $text_response = $consultClientListKeys[0]['textResponse'];
            $last_action = $consultClientListKeys[0]['lastAction'];
            $data = $consultClientListKeys[0]['data'];
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
