<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $descripcion
 * @property int $activo
 * @property-read WsLista[] $wsListas
 */
class WsTipoLista extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_tipo_lista';

    /**
     * @var array
     */
    protected $fillable = ['codigo', 'nombre',
        'descripcion', 'activo',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsListas()
    {
        return $this->hasMany(WsLista::class, 'id_tipo_lista');
    }
}
