<?php
namespace App\Repositories\V2;

use App\Models\BblClientesInfoPagoEnvio;

class ClientShippingRepository
{

    protected $clients;
    public function __construct(BblClientesInfoPagoEnvio $clients)
    {
        $this->clients = $clients;
    }

    public function findOrCreateByCatalogueAndEmail($catalogoId, $email)
    {
        $infoPagoEnvio = $this->findByCatalogueAndEmail($catalogoId, $email);
        if (!$infoPagoEnvio) {
            return new BblClientesInfoPagoEnvio();
        }
        return $infoPagoEnvio;
    }

    public function find($id)
    {

        return $data = $this->clients->where('id', $id)->first();

    }

    public function findByCatalogueAndEmail($catalogue_id, $email)
    {
        return $this->clients->where("catalogo_id", $catalogue_id)->where("email", $email)->first();

    }

}
