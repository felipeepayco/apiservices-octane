<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoCategorias extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_categorias';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha', 'nombre','imagen','catalogo_id','cliente_id'];

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
