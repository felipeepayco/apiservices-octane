<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoTipoEnvio extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_tipo_envio';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'estado'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
