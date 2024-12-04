<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PaypalClientes
 * @package App\Models
 */
class PaypalClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paypal_clientes';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_id',
        'customer_access_token',
        'paypal_user_id',
        'name',
        'email',
        'paypal_user_info_response',
        'last_code',
        'initial_token_bearer',
        'user_balance',
        'moneda_balance',
        'fecha_creacion',
        'refresh_token'
    ];


    /**
     * @var array
     */
    protected $dates = ['fecha_creacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
