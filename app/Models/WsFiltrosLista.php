<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $documento
 * @property string $email
 * @property string $franquicia
 * @property string $ips
 * @property string $lista
 * @property string $numero_tarjeta
 * @property string $tipodoc
 * @property-read Clientes $clientes
 */
class WsFiltrosLista extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'ws_filtros_lista';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'documento', 'email', 'franquicia', 'ips', 'lista', 'numero_tarjeta', 'tipodoc'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function clientes()
    {
        return $this->belongsTo('Clientes', 'cliente_id');
    }
}
