<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Categorias extends Model
{
    protected $table = 'categorias';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'scoring',
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
