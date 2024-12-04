<?php

namespace App\Listeners\Services;

use App\Helpers\Pago\HelperPago;
use App\Models\ModalClientes;

class ModalClientesService extends HelperPago {

    public function __construct()
    {
        // comment
    }

    public function createModalCliente($clientId, $modalConfigId)
    {
        $modalClientes = new ModalClientes();
        
        $modalClientes->cliente_id = $clientId;
        $modalClientes->modal_config_id = $modalConfigId;
        $modalClientes->contador = 0; //false
        $modalClientes->no_ver_mas = 0; //false
        $modalClientes->fecha = new \DateTime('now');
        
        $modalClientes->save();
    }
}