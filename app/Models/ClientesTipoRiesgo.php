<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id_cliente
 * @property $id_tipo_riesgo
 */
class ClientesTipoRiesgo extends Model
{
    protected $table = 'clientes_tipo_riesgo';

    protected $fillable = [
        'id_cliente',
        'id_tipo_riesgo'
    ];

    protected $primaryKey = 'id_cliente';

    public $timestamps = false;
}