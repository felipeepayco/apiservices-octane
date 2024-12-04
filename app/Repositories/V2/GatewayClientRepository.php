<?php
namespace App\Repositories\V2;

use App\Models\BblClientesPasarelas;

class GatewayClientRepository
{

    protected $gateway_clients;
    public function __construct(BblClientesPasarelas $gateway_clients)
    {
        $this->gateway_clients = $gateway_clients;
    }

    public function find($id)
    {

        return $data = $this->gateway_clients->find($id);

    }

    public function findByClientId($id)
    {

        return $data = $this->gateway_clients->where('cliente_id', $id)->first();

    }

}
