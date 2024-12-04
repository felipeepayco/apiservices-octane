<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BblClientesPasarelas extends Model
{
    protected $table = 'bbl_clientes_pasarelas';

    public function BblCliente()
    {
        return $this->belongsTo(BblClientes::class,'cliente_id','id');
    }

    public function BblPasarela()
    {
        return $this->belongsTo(BblPasarela::class,'pasarela_id','id');
    }

}
