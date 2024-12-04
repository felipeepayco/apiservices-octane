<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PaypalRetiros
 * @package App\Models
 */
class PaypalRetiros extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paypal_retiros';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'access_token',
        'paypal_cliente_id',
        'code',
        'monto',
        'saldo_antes',
        'saldo_despues',
        'trm',
        'fecha',
        'status',
        'paypal_response',
        'receiver_fee',
        'total_amount_received',
        'comision',
        'id_cliente',
        'id_paypal_retiro',
        'id_banco',
        'gmf',
        'request_retiro'
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
