<?php
namespace App\Repositories\V2;

use App\Models\BblClientesPasarelas;

class ClientGatewayRepository
{

    protected $clients;
    public function __construct(BblClientesPasarelas $clients)
    {
        $this->clients = $clients;
    }

    public function find($id)
    {

        return $data = $this->clients->find($id);

    }

    public function findByCriteria($arr)
    {

        return $data = $this->clients->where($arr)->first();

    }

}
