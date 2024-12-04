<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bancos extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'pais',
        'codigo_banco',
        'codigo_bancolombia',
        'codigo_davivienda',
        'codigo_recaudo',
        'activo'
        ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
