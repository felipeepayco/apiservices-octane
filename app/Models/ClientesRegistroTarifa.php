<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientesRegistroTarifa
 * @package App\Models
 * @property int $id
 * @property int $cliente_id
 * @property int $tarifa
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $fecha_fin
 * @property int $tope_transaccion
 * @property bool $vincula_cuenta_davivienda
 */
class ClientesRegistroTarifa extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_registro_tarifa';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_id',
        'tarifa',
        'created_at',
        'fecha_fin',
        'tope_transaccion',
        'vincula_cuenta_davivienda',
    ];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'fecha_fin'];

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
