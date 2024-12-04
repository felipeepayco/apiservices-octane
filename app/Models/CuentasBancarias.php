<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $numero_tarjeta
 * @property string $numero_corto
 * @property int $banco_id
 * @property int $tipo_cuenta_id
 * @property int $cliente_id
 * @property int $estado_id
 * @property int $respuesta_id
 * @property int predeterminada
 * @property string $tipo_cuenta_davivienda
 * @property \Carbon\Carbon $fecha_apertura
 */
class CuentasBancarias extends Model
{
    protected $table = 'cuentas_bancarias';

    /**
     * @var array
     */
    protected $fillable = [
        'numero_tarjeta',
        'numero_corto',
        'banco_id',
        'tipo_cuenta_id',
        'cliente_id',
        'estado_id',
        'respuesta_id',
        'tipo_cuenta_davivienda',
        'fecha_apertura',
        'apertura_davivienda',
        'etiqueta',
        'predeterminada'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $dates = ['fecha_apertura'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
