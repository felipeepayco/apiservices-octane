<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BblSuscripcionCargos extends Model
{
    protected $table = 'bbl_suscripcion_cargos';
    protected $fillable=[
        'ref_payco',
        'factura',
        'descripcion',
        'valor',
        'valor_neto',
        'moneda',
        'respuesta',
        'recibo',
        'fecha',
        'fecha_confirmacion',
        'suscripcion_id',
        'confirmacion',
        'transaccion_id',
        'suscripcion_cliente_id',
        'estado'
    ];

}
