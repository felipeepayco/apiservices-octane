<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class WsFiltrosLogControl extends Model
{
    /**
     * @var string
     */
    protected $table = 'ws_filtros_log_control';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha', 'filtro_id', 'superado',
        'transaccion_id', 'valor', 'valorcomparativo', 'costo', 'moneda'
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
