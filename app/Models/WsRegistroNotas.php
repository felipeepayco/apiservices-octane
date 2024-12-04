<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_registro
 * @property string $nota
 * @property int $activo
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_actualizacion
 */
class WsRegistroNotas extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_registro_notas';

    /**
     * @var array
     */
    protected $fillable = [
        'id_registro',
        'nota',
        'activo',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
}