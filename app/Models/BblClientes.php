<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BblClientes extends Model
{
    protected $table = 'bbl_clientes';

    public function tipo_documento()
    {
        return $this->belongsTo(TipoDocumentos::class,'tipo_doc','id');
    }


    public function subscriptions()
    {
        return $this->hasMany(BblSuscripcion::class, 'bbl_cliente_id');
    }




}
