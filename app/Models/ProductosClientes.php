<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_inicio
 * @property \Carbon\Carbon $fecha_periodo
 * @property \Carbon\Carbon $fecha_renovacion
 * @property \Carbon\Carbon $fecha_cancelacion
 * @property boolean $estado
 * @property int $cliente_id
 * @property int $producto_id
 * @property int $periocidad
 * @property string $observations
 * @property string $configuracion
 * @property int $facturar_a_cliente_id
 * @property int $precio
 */
class ProductosClientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_periodo',
        'fecha_renovacion',
        'fecha_cancelacion',
        'estado',
        'cliente_id',
        'producto_id',
        'periocidad',
        'observations',
        'facturar_a_cliente_id',
        'precio',
        'configuracion',
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion', 'fecha_inicio', 'fecha_periodo','fecha_renovacion','fecha_cancelacion'];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * Get the product that owns the product_client.
     */
    public function product()
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
