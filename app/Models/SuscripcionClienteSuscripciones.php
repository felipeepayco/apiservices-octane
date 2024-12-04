<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuscripcionClienteSuscripciones extends Model
{
    protected $primaryKey = 'id';

    protected $table = 'suscripcion_cliente_suscripciones';

    protected $fillable = [
        'suscripcion_proyecto_id',
        'suscripcion_cliente_id',
        'suscripcion_mongo_id',
        'fecha_creacion',
        'estado',
        'parametros_consulta',
    ];

    public $timestamps = false;

    protected $dates = ['fecha_creacion'];
}
