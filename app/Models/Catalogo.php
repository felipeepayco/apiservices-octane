<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Catalogo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha', 'nombre','imagen','cliente_id','estado'];

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
