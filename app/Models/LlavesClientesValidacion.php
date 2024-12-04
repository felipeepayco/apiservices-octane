<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $private_key
 * @property string $public_key
 * @property \Carbon\Carbon $fechacreacion
 * @property \Carbon\Carbon $fechaactualizacion
 * @property int $cliente_id
 */
class LlavesClientes extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'llaves_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'private_key', 'public_key', 'fechacreacion',
        'fechaactualizacion', 'cliente_id', 'private_key_decrypt'];

    /**
     * @var array
     */
    protected $dates = ['fechacreacion', 'fechaactualizacion'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
