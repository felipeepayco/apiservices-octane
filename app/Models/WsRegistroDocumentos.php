<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_registro
 * @property string $nombre
 * @property string $url
 * @property \Carbon\Carbon $fecha_creacion
 */
class WsRegistroDocumentos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_registro_documentos';

    /**
     * @var array
     */
    protected $fillable = [
        'id_registro',
        'url',
        'urlS3',
        'bucketName',
        'nombre',
        'tipo',
        'activo',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
}