<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property float $valor
 * @property \Carbon\Carbon $fecha_inicial
 * @property \Carbon\Carbon $fecha_vencimiento
 * @property int $tipo
 * @property string $codigo
 * @property string $mensajeok
 * @property int $estado
 * @property int $recurrente
 * @property int $cantidad
 * @property string $ciclo_facturacion
 */
class CuponesDescuento extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cupones_descuento';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'codigo',
        'valor',
        'fecha_inicial',
        'fecha_vencimiento',
        'tipo',
        'mensajeok',
        'estado',
        'recurrente',
        'cantidad',
        'ciclo_facturacion'
    ];
    /**
     * @var array
     */
    protected $dates = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}