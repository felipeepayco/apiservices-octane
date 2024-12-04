<?php

namespace App\Repositories\V2;

use App\Models\V2\BblBuyer;
use DB;

class BblBuyerRepository
{
    protected $bblBuyer;
    public $documento;
    public $bblClienteId;

    public function __construct(BblBuyer $bblBuyer)
    {
        $this->buyers = $bblBuyer;
    }

    public function findBuyerByDocumentAndClientId($except = 0)
    {

        $buyer = $this->buyers->where('documento', $this->documento)->where("bbl_cliente_id", $this->bblClienteId);

        if ($except) {
            $buyer = $buyer->where('id', '<>', $except);

        }
        return $buyer->first();
    }
    public function create($data)
    {
        return $this->buyers->create($data);
    }

    public function update($data, $criteria)
    {

        $update_data = [
            'bbl_cliente_id' => $data['clientId'],
            'correo' => $data['email'],
            'nombre' => $data['firstName'],
            'apellido' => $data['lastName'],
            'documento' => $data['document'],
            'telefono' => $data['clientPhone'],
            'ind_pais_tlf' => $data['countryCode'],
            'pais' => $data['country'],
            'codigo_pais' => $data['countryCode2'],
            'codigo_dane' => $data['codeDane'],
            'departamento' => $data['department'],
            'ciudad' => $data['city'],
            'direccion' => $data['address'],
            'otros_detalles' => $data['other'],
        ];

        if (isset($data["lastPurchase"])) {
            $update_data["ultima_compra"] = $data["lastPurchase"];

        }

        if (isset($data["totalConsumedAmount"])) {
            $update_data["monto_total_consumido"] = $data["totalConsumedAmount"];
        }

        return $this->buyers->where($criteria)->update($update_data);
    }

    public function get($client_id)
    {

        return $data = $this->buyers->get();

    }

    public function find($id)
    {

        return $data = $this->buyers->find($id);

    }

    public function getByCriteria($arr)
    {

        $buyers = $this->buyers
            ->select(
                'bbl_comprador.id',
                'nombre as firstName',
                'apellido as lastName',
                DB::raw('CONCAT(bbl_comprador.nombre," ", bbl_comprador.apellido) as fullName')
                ,
                'correo as email',
                'telefono as cellphone',
                'pais as country',
                'codigo_pais as countryCode',
                'codigo_dane as codeDane',
                'ciudad as city',
                'documento as document',
                'departamento as department',
                'direccion as direction',
                'monto_total_consumido as totalConsumedAmount',
                'ultima_compra as lastPurchase',
                'otros_detalles as otherDetails',
                'ind_pais_tlf as phoneCode',
                'bbl_comprador.created_at as createdAt'
            )
            ->where($arr);

        return $buyers = $buyers
            ->orderBy('bbl_comprador.created_at', 'DESC')
            ->get();

    }

    public function findByCriteria($arr)
    {

        $buyers = $this->buyers->where($arr);

        return $buyers = $buyers->first();

    }

    public function destroy($id)
    {

        return $buyers = $this->buyers->destroy($id);

    }


    public function destroyByCriteria($arr)
    {

        $buyers = $this->buyers->where($arr);

        return $buyers = $buyers->delete();

    }

    public function findOrCreateByclientIdAndEmail($clientId, $email)
    {
        $infoBuyer = $this->findByClientIdAndEmail($clientId, $email);
        if (!$infoBuyer) {
            return new BblBuyer();
        }
        return $infoBuyer;
    }

    public function findByClientIdAndEmail($clientId, $email)
    {
        return $this->buyers->where("bbl_cliente_id", $clientId)->where("correo", $email)->first();
    }

}
