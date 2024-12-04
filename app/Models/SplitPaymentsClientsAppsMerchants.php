<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SplitPaymentsClientsAppsMerchants extends Model
{
    protected $table = 'splitpayments_clientesapps_merchants';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha',
        'clienteapp_id',
        'merchant_receiver_id',
        'tipo_comision',
        'valor_comision',
        'estado'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}

