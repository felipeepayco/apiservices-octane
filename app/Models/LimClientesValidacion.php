<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $porcentaje
 * @property int $estado_id
 * @property int $validacion_id
 */
class LimClientesValidacion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lim_clientes_validacion';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'porcentaje', 'estado_id','validacion_id'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
