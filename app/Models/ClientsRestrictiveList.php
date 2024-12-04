<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientsRestrictiveList
 * @package App\Models
 * @property int $id
 * @property int $id_cliente
 * @property int $id_pre_registro
 * @property int $id_entidad_aliada
 * @property string $tipo_doc
 * @property string $numero_documento
 * @property string $usuario_lista
 * @property string $id_tipo_validacion
 * @property string $id_tipo_riesgo
 * @property string $request_service
 * @property string $response_service
 * @property boolean $estado_reportado
 * @property int $digito
 * @property $fecha_creacion
 */
class ClientsRestrictiveList extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_listas_restrictivas';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_cliente',
        'id_pre_registro',
        'tipo_doc',
        'numero_documento',
        'id_entidad_aliada',
        'fecha_creacion',
        'usuario_lista',
        'id_tipo_validacion',
        'id_tipo_riesgo',
        'request_service',
        'response_service',
        'estado_reportado',
        'digito',
    ];

    public $timestamps = false;
}
