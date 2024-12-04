<?php
namespace App\Repositories\V2;

use App\Models\BblDiscountCode;

class DiscountCodeRepository
{

    protected $discount_codes;
    public function __construct(BblDiscountCode $discount_codes)
    {
        $this->discount_codes = $discount_codes;
    }

    public function find($id)
    {

        return $data = $this->discount_codes->find($id);

    }

    public function findByName($name, $client_id = null)
    {
        $query = $this->discount_codes->where("nombre", $name);

        if ($client_id) {
            $query = $this->discount_codes->where("cliente_id", $client_id);

        }
        return $query->first();
    }

    public function destroyByCriteria($arr)
    {

        $query = $this->discount_codes->where($arr);

        return $query = $query->delete();

    }

}
