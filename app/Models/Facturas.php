<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Facturas extends Model
{
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
        'descuento',
        'valor_descuento',
        'subtotal',
        'iva',
        'retencion_enlafuente',
        'total_neto',
        'retefuente',
        'reteiva',
        'reteica',
        'otrosimpuestos',
        'observaciones',
        'id_estado',
        'codinterno',
        'cupon_id',
        'id_factura_proforma',
        'id_factura_siigo',
        'visible',
        'porc_iva',
        'retencion_iva',
        'porc_retencion_iva',
        'retencion_ica',
        'porc_retencion_ica',
        'porc_retencion_enlafuente',
        'consecutivo_factura_siigo',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
