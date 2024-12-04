<?php

namespace App\Repositories\V2;

use App\Models\BblSuscripcion;

class BblSubscriptionRepository
{
    protected $bblSubscription;

    public function __construct(BblSuscripcion $bblSubscription)
    {
        $this->bblSubscription = $bblSubscription;
    }

    public function create($data)
    {
        return $this->bblSubscription->create($data);
    }

    public function update($data, $criteria)
    {

        return $this->bblSubscription->where($criteria)->update($data);
    }

    public function find($id)
    {

        return $data = $this->bblSubscription->find($id);

    }

    public function destroy($id)
    {

        return $bblSubscription = $this->bblSubscription->destroy($id);

    }

    public function destroyByCriteria($arr)
    {

        $bblSubscription = $this->bblSubscription->where($arr);

        return $bblSubscription = $bblSubscription->delete();

    }

}
