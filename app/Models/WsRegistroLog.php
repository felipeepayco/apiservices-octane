<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_registro
 * @property int $id_usuario_manage
 * @property int id_usuario_dashboard
 * @property string accion
 * @property string $detalle
 * @property \Carbon\Carbon $fecha_creacion
 */
class WsRegistroLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_registro_log';

    /**
     * @var array
     */
    protected $fillable = ['id_registro', 'id_usuario_manage',
        'id_usuario_dashboard', 'accion', 'detalle', 'fecha_creacion'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_creacion';
}
