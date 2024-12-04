<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $filtro
 * @property int $estado
 * @property int $id_cliente
 * @property string $valor
 * @property-read WsFiltros $wsFiltros
 */
class WsFiltrosDefault extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'ws_filtros_default';

    /**
     * @var array
     */
    protected $fillable = ['id', 'filtro', 'estado', 'id_cliente', 'valor'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wsFiltros()
    {
        return $this->belongsTo('WsFiltros', 'filtro');
    }
}
