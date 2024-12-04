<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $accion
 * @property int $cliente_id
 * @property \Carbon\Carbon $fecha
 * @property string $request
 * @property string $response
 * @property string $red
 */
class TerminalLog extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'terminal_log';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'request', 'response','red', 'fecha','accion'];

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
