<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WSFiltrosMontos
 * @package App\Models
 */
class WSFiltrosMontos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_filtros_montos';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'tipo_cliente',
        'filtro_id',
        'nombre',
        'valor',
        'moneda',
        'pais'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
