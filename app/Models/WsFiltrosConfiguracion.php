<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WsFiltrosConfiguracion
 * @package App\Models
 * @property $id
 * @property $id_configuracion_regla
 * @property $id_filtro
 * @property $activo
 * @property $valor
 * @property $score
 * @property $orden
 * @property $estado
 * @property $moneda
 * @property $fecha_creacion
 * @property $fecha_actualizacion
 */
class WsFiltrosConfiguracion extends Model
{
    protected $table = 'ws_filtros_configuracion';

    protected $fillable = [
        'id_configuracion_regla',
        'id_filtro',
        'activo',
        'valor',
        'score',
        'orden',
        'estado',
        'moneda',
        'email_cliente',
        'id_cliente'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
}