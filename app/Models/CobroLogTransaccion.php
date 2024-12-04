<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CobroLogTransaccion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cobros_log_transacciones';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $fillable = [
        'cobro_id', 
        'transaction_id', 
        'disponibles',
        'cantidad',
        'total_disponibles',
        'created_at', 
        'updated_at'
    ];

    /**
     * @var array
     */
    protected $dates = ['created_at','updated_at'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
