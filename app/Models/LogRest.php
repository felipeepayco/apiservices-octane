<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $accion
 * @property int $cliente_id
 * @property \Carbon\Carbon $fechafin
 * @property \Carbon\Carbon $fechainicio
 * @property int $ip
 * @property string $microtime
 * @property string $request
 * @property string $response
 * @property string $session_id
 */
class LogRest extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_rest';

    /**
     * @var array
     */
    protected $fillable = ['id', 'accion', 'cliente_id', 'fechafin',
        'fechainicio', 'ip', 'microtime', 'request', 'response', 'session_id'];

    /**
     * @var array
     */
    protected $dates = ['fechafin', 'fechainicio'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
