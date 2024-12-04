<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_configuracion_regla
 * @property int $id_entidad
 * @property int $id_tipo_plan
 * @property int $id_codigo_ciiu
 * @property int $id_subcategorias
 * @property string $nombre
 * @property int $activo
 * @property string $tipo_cliente
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_actualizacion
 */
class WsPlantilla extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_plantilla';

    /**
     * @var array
     */
    /**
     * @var array
     */
    protected $fillable = ['id', 'id_configuracion_regla', 'id_entidad', 'id_tipo_plan', 'id_codigo_ciiu',
        'id_subcategorias', 'nombre', 'predeterminada', 'activo', 'tipo_cliente'];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * Many to one relationship with ws_configuracion_regla
     */
    public function wsConfigRules() {
        return $this->belongsTo(WsConfiguracionRegla::class, 'id_configuracion_regla');
    }
}
