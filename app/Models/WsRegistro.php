<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_transaccion
 * @property int $id_estado_econtrol
 * @property int $id_estado_gestion
 * @property int $puntaje
 * @property int $reglas_activadas
 * @property bool $verificado
 * @property \Carbon\Carbon $fecha_gestion
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_actualizacion
 */
class WsRegistro extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_registro';

    /**
     * @var array
     */
    protected $fillable = ['id_transaccion', 'id_estado_econtrol', 'id_estado_gestion',
        'puntaje', 'reglas_activadas', 'verificado', 'fecha_gestion'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * Many to one relationship with transacciones
     */
    public function transaction() {
        return $this->belongsTo(Transacciones::class, 'id_transaccion');
    }
}
