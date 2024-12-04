<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $cobro_id
 * @property int $transaction_id
 * @property int $disponibles
 * @property int $cantidad
 * @property int $total_disponibles
 * @property-read Transacciones[] $transacciones
 */
class CobrosLogTransacciones extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cobros_log_transacciones';

    /**
     * @var array
     */
    protected $fillable = ['created_at', 'updated_at', 'cobro_id',
        'transaction_id', 'disponibles', 'cantidad', 'total_disponibles',
    ];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function transacciones()
    {
        return $this->hasOne('App\Models\Transacciones', 'transaction_id');
    }
}
