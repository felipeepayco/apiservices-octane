<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class FacturasProforma extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'facturas_proforma';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'tipo',
        'fecha',
        'fecha_periodo',
        'fecha_limite',
        'fecha_pago',
        'id_cliente',
        'consecutivo',
        'concepto',
        'total',
        'iva',
        'descuento',
        'valor_descuento',
        'subtotal',
        'retefuente',
        'reteiva',
        'reteica',
        'retencion_enlafuente',
        'otrosimpuestos',
        'total_neto',
        'observaciones',
        'id_estado',
        'codinterno',
        'cupon_id',
        'porc_iva',
        'porc_retencion_enlafuente',
        'retencion_iva',
        'porc_retencion_iva',
        'retencion_ica',
        'porc_retencion_ica'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
