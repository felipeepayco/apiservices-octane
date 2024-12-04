<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadosRetiros
 * @package App\Models
 */
class EstadosRetiros extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'estados_retiros';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'estado',
        'descripcion',
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
