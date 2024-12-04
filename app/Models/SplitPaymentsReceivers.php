<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SplitPaymentsReceivers extends Model
{
    protected $table = 'splitpayments_receivers';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha',
        'clienteapp_id',
        'merchant_receiver_id',
        'receiver_id',
        'estado',
        'tipo_comision',
        'valor_comision'
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

