<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_tipo_lista
 * @property int $id_cliente
 * @property string $nombre
 * @property int $activo
 * @property int $eliminado
 * @property $fecha_creacion
 * @property $fecha_actualizacion
 * @property-read Clientes[] $cliente
 * @property-read WsTipoLista[] $wsTipoLista
 * @property-read WsListaItems[] $wsListaItems
 */
class WsLista extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_lista';

    /**
     * @var array
     */
    protected $fillable = [
        'id_tipo_lista',
        'id_cliente',
        'nombre',
        'activo',
        'eliminado',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wsTipoLista()
    {
        return $this->belongsTo(WsTipoLista::class, 'id_tipo_lista');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsListaItems()
    {
        return $this->hasMany(wsListaItems::class, 'id_lista');
    }
}
