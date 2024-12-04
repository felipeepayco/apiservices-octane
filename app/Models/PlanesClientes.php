<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Carbon\Carbon $fecha_solicitud
 * @property \Carbon\Carbon $fecha_activacion
 * @property int $cliente_id
 * @property int $plan_id
 * @property int $estado


 * @property string $porcentaje
 */
class PlanesClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'planes_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha_solicitud', 'fecha_activacion', 'cliente_id','plan_id','estado'];

    /**
     * @var array
     */
    protected $dates = ['fecha_solicitud','fecha_activacion'];
      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
