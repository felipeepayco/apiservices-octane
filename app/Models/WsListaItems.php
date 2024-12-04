<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_lista
 * @property int $activo
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_actualizacion
 * @property string $tipodoc
 * @property string $documento
 * @property string $numero_tarjeta
 * @property int $bin
 * @property string $franquicia
 * @property string $banco
 * @property string $ips
 * @property string $email
 * @property float $monto
 * @property string $moneda
 * @property-read WsLista[] $wsLista
 */
class WsListaItems extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_lista_items';

    /**
     * @var array
     */
    protected $fillable = [
        'id_lista',
        'activo',
        'tipodoc',
        'documento',
        'numero_tarjeta',
        'bin',
        'franquicia',
        'banco',
        'ips',
        'email',
        'monto',
        'moneda',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wsLista()
    {
        return $this->belongsTo(WsLista::class, 'id_lista');
    }
}