<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_factura
 * @property string $item
 * @property int $cantidad
 * @property float $valor_unitario
 * @property float $subtotal
 * @property float $totalacumulado
 * @property int $producto_id
 * @property int $producto_cliente_id
 */

class DetalleFacturasProforma extends Model
{


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'detalle_facturas_proforma';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_factura',
        'item',
        'cantidad',
        'valor_unitario',
        'subtotal',
        'totalacumulado',
        'producto_id',
        'producto_cliente_id'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
