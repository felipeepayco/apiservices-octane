<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cantidad
 * @property int $cantidad_generados
 * @property bool $enviado
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_generacion
 * @property string $value
 * @property int $id_recaudo_facturas_lote
 * @property bool $disponible
 */
class RecaudoFacturasEmail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_facturas_email';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'enviado',
        'value',
        'cantidad',
        'id_recaudo_facturas_lote',
        'fecha_creacion',
        'fecha_generacion',
        'cantidad_generados',
        'disponible'
        ];

    /**
     * @var array
     */
    protected $dates = [
        'fecha_creacion',
        'fecha_generacion'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
