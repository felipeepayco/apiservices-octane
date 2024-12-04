<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 */
class TipoMovimiento extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipomovimiento';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'operacion',
        'nota'
    ];


    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
