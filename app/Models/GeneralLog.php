<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre_cron
 * @property string $nombre_artefacto
 * @property int $cliente_id
 * @property string $detalles
 * @property string $error_mensaje
 * @property \Carbon\Carbon $fecha_creacion
 */
class GeneralLog extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'general_log';

    /**
     * The primary key associated with the table.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'accion',
        'mensaje',
        'detalle',
        'peticion_externa',
        'url',
        'fecha'
    ];

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
