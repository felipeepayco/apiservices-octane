<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientesAplicarTarifa
 * @package App\Models
 * @property int $id
 * @property int $cliente_id
 * @property boolean $activo
 * @property float $comision
 * @property \Carbon\Carbon $fecha_limite
 */
class ClientesAplicarTarifa extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_aplicar_tarifa';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_id',
        'activo',
        'comision',
        'fecha_limite',
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha_limite'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
