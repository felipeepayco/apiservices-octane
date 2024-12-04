<?php

namespace App\Listeners\Services;

use App\Common\TrConfigapiRedId;
use App\Models\MediosPago;
use App\Models\MediosPagoClientes;
use App\Models\PseConfigSitio;
use App\Models\TrConfigapiRed;
use App\Models\TrSitio;

class ClientNetworksService
{

    public function getNetworksAndMethods(&$networks)
    {
        $networksAndMethods = TrConfigapiRed::select("id")
            ->addSelect("nombre_red as name")
            ->whereIn('proveedor', ['redeban', 'credibanco'])
            ->where('enabled', true)->get();

        foreach ($networksAndMethods as &$networksAndMethod) {
            $paymentMethods = MediosPago::select("medios_pago.Id as id")
                ->addSelect("medios_pago.nombre as name")
                ->leftJoin("network_payment_method", "network_payment_method.payment_method_id", "medios_pago.Id")
                ->where('network_payment_method.network_id', $networksAndMethod["id"])->get();

            $networksAndMethod["paymentMethods"] = $paymentMethods;
            array_push($networks, $networksAndMethod["id"]); //se captura los ids de las redes activas
        }

        return [
            "networks" => $networksAndMethods
        ];
    }

    public function getNetworkConfigurationAndMethods($clientId, $networks)
    {
        $creditConfig = TrSitio::select("id")
            ->addSelect('codigounico as uniqueCode')
            ->addSelect('terminalcode as terminalCode')
            ->addSelect('email_notificaciones as emailNotifications')
            ->addSelect('certificado_txt as certificate')
            ->addSelect('certificadokey_txt as certificateKey')
            ->addSelect('username')
            ->addSelect('password')
            ->addSelect('red as network')
            ->addSelect('id_config_api as networkId')
            ->whereIn('id_config_api', $networks)
            ->where('cliente_id', $clientId)
            ->where('fecha_eliminacion', null)
            ->get();

        foreach ($creditConfig as &$config) {
            $clientPaymentMethods = MediosPagoClientes::select("*")
                ->where('id_cliente', $clientId)
                ->where('red', $config["networkId"])->get();

            $paymentMethods = [];
            if ($clientPaymentMethods) {
                foreach ($clientPaymentMethods as $clientPaymentMethod) {
                    array_push($paymentMethods, $clientPaymentMethod["id_medio"]);
                }
            }

            $config["paymentMethods"] = $paymentMethods;
        }

        $daviplataConfig = TrSitio::select("id")
            ->addSelect('red as network')
            ->addSelect('codigounico as uniqueCode')
            ->addSelect('terminalcode as terminalCode')
            ->where('cliente_id', $clientId)
            ->where('red', 'daviplata')
            ->first();

        $pseConfig = PseConfigSitio::select("id")
            ->addSelect('entity_code as entityCode')
            ->addSelect('service_code as serviceCode')
            ->where('cliente_id', $clientId)
            ->first();

        return [
            "creditConfig" => $creditConfig,
            "daviplataConfig" => $daviplataConfig,
            "pseConfig" => $pseConfig
        ];
    }

    public function validateEdition($data, &$clientNetwork) {
        $response = ['success' => true];
        // Si estan editando se busca la red para editarla
        if (isset($data['id'])) {
            $clientNetwork = $this->searchNetworkForEdit(
                $data['id'],
                $data['networkId'],
                $data['clientId']
            );

            if (!$clientNetwork){
                $response = [
                    'success' => false,
                    'titleResponse' => trans('message.Create or update client network'),
                    'textResponse' => trans('message.Client network does not exists'),
                    'lastAction' => trans('message.Query client network'),
                    'data' => [],
                ];
            }
        } else {
            //si el cliente no esta editando, validar si ya tiene una red con ese networkID
            $hasNetwork = $this->clientHasNetwork($data['clientId'], $data['networkId']);
            if ($hasNetwork) {
                $response = [
                    'success' => false,
                    'titleResponse' => trans('message.Create or update client network'),
                    'textResponse' => trans('message.The client already has a network type: ') . $data['networkId'],
                    'lastAction' => trans('message.Query client network'),
                    'data' => [],
                ];
            }
        }
        return $response;
    }

    public function setNameNetwork(int $networkId)
    {
        switch ($networkId) {
            // Para futuras redes
            case 2:
                $network = 'redeban';
                break;
            case 4:
                $network = 'credibanco';
                break;
            case 5:
                $network = 'PSE';
                break;
            case 8:
                $network = 'credibancoVNP';
                break;
            case 9:
                $network = 'daviplata';
                break;
            default:
                $network = null;
        }
        return $network;
    }

    /**
     * @param int $networkId
     * @param int $typeNetworkId
     * @param int $clientId
     * @return mixed
     */
    private function searchNetworkForEdit(int $networkId, int $typeNetworkId, int $clientId)
    {
        if ($typeNetworkId === TrConfigapiRedId::PSE) {
            return PseConfigSitio::where('id', $networkId)
                ->where('cliente_id', $clientId)
                ->first();
        } else {
            return TrSitio::where('id', $networkId)
                ->where('cliente_id', $clientId)
                ->where('id_config_api', $typeNetworkId)
                ->where('fecha_eliminacion', null)
                ->first();
        }
    }

    /**
     * @param int $clientId
     * @param int $typeNetworkId
     * @return mixed
     */
    private function clientHasNetwork(int $clientId, int $typeNetworkId)
    {
        if ($typeNetworkId === TrConfigapiRedId::PSE) {
            return PseConfigSitio::where('cliente_id', $clientId)
                ->first();
        } else {
            return TrSitio::where('cliente_id', $clientId)
                ->where('id_config_api', $typeNetworkId)
                ->where('fecha_eliminacion', null)
                ->first();
        }
    }

}
