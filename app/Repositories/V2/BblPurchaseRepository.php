<?php

namespace App\Repositories\V2;

use App\Common\TransactionStateCodes;
use App\Models\V2\BblPurchase;

class BblPurchaseRepository
{
    protected $bblPurchase;

    public function __construct(BblPurchase $bblPurchase)
    {
        $this->purchase = $bblPurchase;
    }

    public function create($data)
    {
        return $this->purchase->create($data);
    }

    public function update($data, $criteria)
    {
        return $this->purchase->where($criteria)->update($data);
    }

    public function find($id)
    {

        return $data = $this->purchase->find($id);

    }

    public function destroy($id)
    {

        return $purchase = $this->purchase->destroy($id);

    }

    public function getByCriteria($arr)
    {

        $purchases = $this->purchase
        ->select(
            'id',
            'carrito_id as cardId',
            'monto as amount',
            'fecha as date',
            'estado as status',
            'bbl_comprador_id as bblBuyerId',
            'referencia_epayco as refEpayco',
            'cantidad_productos as quantityProducts',
            )
            ->where($arr);

        return $purchases = $purchases
            ->orderBy('bbl_compras.created_at', 'DESC')
            ->get();

    }

}
