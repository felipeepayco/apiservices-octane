<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WsFiltrosCostos
 * @package App\Models
 */
class WsFiltrosCostos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ws_filtros_costos';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_filtro',
        'id_pais',
        'monto',
        'moneda',
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
