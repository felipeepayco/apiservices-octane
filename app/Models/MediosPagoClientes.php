<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $Id
 * @property int $id_cliente
 * @property string $id_medio
 * @property int $estado
 * @property int $bancaria_id
 * @property mixed $comision
 * @property int $valor_comision
 * @property int $red
 */
class MediosPagoClientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['Id', 'id_cliente', 'id_medio',
        'estado', 'bancaria_id', 'comision', 'valor_comision', 'red'];

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

    public function paymentMethod()
    {
        return $this->belongsTo(MediosPago::class, 'id_medio', 'Id');
    }
}
