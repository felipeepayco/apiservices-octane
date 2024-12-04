<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposCuenta extends Model
{


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_cuenta';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'tipo',
        'descripcion'
        ];
    

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
