<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 */
class TransaccionesLogConfirm extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transacciones_logconfirm';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'transaccion_id',
        'fecha',
        'urlenvio',
        'send_text',
        'response_text',
    ];


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
