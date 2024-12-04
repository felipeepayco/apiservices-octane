<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $filtro
 * @property int $estado
 * @property int $id_cliente
 * @property int $scoring
 * @property string $valor
 * @property-read WsFiltros $wsFiltros
 */
class WsFiltrosClientes extends Model
{
    protected $table = 'ws_filtros_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'filtro', 'id_cliente', 'valor', 'estado', 'score'];

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
