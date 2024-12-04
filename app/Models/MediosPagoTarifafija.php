<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property mixed $valormaximo
 * @property mixed $valor_comision
 * @property mixed $valor_comision_cliente
 * @property int $estado
 */
class MediosPagoTarifafija extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'medios_pago_tarifafija';

    /**
     * @var array
     */
    protected $fillable = ['id', 'valormaximo',
        'valor_comision', 'valor_comision_cliente', 'estado'];

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
