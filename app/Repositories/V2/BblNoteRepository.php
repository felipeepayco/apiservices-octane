<?php

namespace App\Repositories\V2;

use App\Models\V2\BblNote;

class BblNoteRepository
{
    protected $bblNote;
    public $bblClienteId;

    public function __construct(bblNote $bblNote)
    {
        $this->notes = $bblNote;
    }

    public function create($data)
    {
        return $this->notes->create($data);
    }

    public function update($data, $criteria)
    {

        return $this->notes->where($criteria)->update($data);
    }

    public function get($client_id)
    {

        return $data = $this->notes->get();

    }

    public function find($id)
    {

        return $data = $this->notes->find($id);

    }

    public function getByCriteria($arr)
    {

        $notes = $this->notes
            ->select(
                'id',
                'nota as note',
                'bbl_comprador_id as bblBuyerId',
                'created_at as createdAt',
                'updated_at as updatedAt',
            )
            ->where($arr);

        return $notes = $notes
            ->orderBy('created_at', 'DESC')
            ->get();

    }

    public function findByCriteria($arr)
    {

        $notes = $this->notes->where($arr);

        return $notes = $notes->first();

    }

    public function destroy($id)
    {

        return $notes = $this->notes->destroy($id);

    }

}
