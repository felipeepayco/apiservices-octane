<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductosFiles extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos_files';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'estado',
        'url',
        'posicion',
        'catalogo_productos_id',
        'fechacreacion'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
