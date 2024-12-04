<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Carbon\Carbon $fecha
 * @property int $tipomovimiento
 * @property int $idregistro
 * @property string $descripcion
 * @property mixed $saldo_retenido
 * @property mixed $saldo_reserva
 * @property mixed $saldoanterior
 * @property mixed $valor
 * @property mixed $nuevosaldo
 * @property int $id_cliente
 */
class Movimientos extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha', 'tipomovimiento', 'idregistro', 'descripcion', 'saldo_retenido', 'saldo_reserva', 'saldoanterior', 'valor', 'nuevosaldo', 'id_cliente'];

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
