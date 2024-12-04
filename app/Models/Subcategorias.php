<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Subcategorias extends Model
{
    protected $table = 'subcategorias';

    /**
     * @var array
     */
    protected $fillable = [
        'id_categoria',
        'nombre',
        'descripcion',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
