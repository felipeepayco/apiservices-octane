<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property int $cuestionario_id
 * @property int $aciertos
 * @property string $respuesta
 * @property string $fecha
 * @property string $fecha_proximo_intento
 */
class LimConfrontaLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lim_confronta_log';

    /**
     * @var array
     */
    protected $fillable = ['cliente_id', 'cuestionario_id', 'aciertos', "respuesta"];

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

    /**
     * @var array
     */
    protected $dates = ['fecha', 'fecha_proximo_intento'];

}
