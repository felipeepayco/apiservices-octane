<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CuentasBancariasRespuestas extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'respuesta',
        'descripcion',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
