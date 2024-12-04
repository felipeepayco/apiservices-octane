<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductosReferenciasFiles extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos_referencias_files';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'url',
        'id_catalogo_productos_referencias',
        'posicion',
        'estado'
        ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
