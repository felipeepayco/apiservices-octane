<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductosEnvio extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos_envio';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_catalogo_productos',
        'id_catalogo_tipo_envio',
        'estado',
        'valor'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
