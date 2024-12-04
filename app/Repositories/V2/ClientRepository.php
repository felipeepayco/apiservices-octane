<?php
namespace App\Repositories\V2;

use App\Models\BblClientes;

class ClientRepository
{

    protected $clients;
    public function __construct(BblClientes $clients)
    {
        $this->clients = $clients;
    }

    public function find($id)
    {

        return $data = $this->clients->find($id);

    }

    public function subscriptionState($id)
    {
        $bblClient = $this->clients->find($id);
        return $sub = $bblClient->subscriptions()->orderBy("created_at", "DESC")->first();
    }

    public function consultDomain($url)
    {
        return $domainConfig = $this->clients->where("url", "like", "https://" . $url)
            ->first();

    }

    public function updateCname($id, $url)
    {
        $data = $this->clients->find($id);
        $data->cname = $url;
        $data->update();
        return $data;
    }

}
