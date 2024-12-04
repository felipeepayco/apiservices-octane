<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SplitPaymentsClientsApps extends Model
{
    protected $table = 'splitpayments_clientesapps';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha',
        'clienteapp_id',
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

