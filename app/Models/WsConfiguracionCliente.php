<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WsConfiguracionCliente
 * @package App\Models
 * @property $id
 * @property $id_configuracion_regla
 * @property $id_cliente
 * @property $activo
 * @property $fecha_creacion
 * @property $fecha_actualizacion
 */
class WsConfiguracionCliente extends Model
{
    protected $table = 'ws_configuracion_cliente';

    protected $fillable = [
        'id_configuracion_regla',
        'id_cliente',
        'activo',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * Many to one relationship with ws_configuracion_regla
     */
    public function wsConfigRules() {
        return $this->belongsTo(WsConfiguracionRegla::class, 'id_configuracion_regla');
    }
}