<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductosCategorias extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos_categorias';

    /**
     * @var array
     */
    protected $fillable = ['id', 'catalogo_categoria_id', 'catalogo_producto_id'];
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
