<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $medio_pago_id
 * @property mixed $valormaximo
 * @property mixed $valor_comision_cliente
 * @property int $estado
 * @property-read Clientes $clientes
 */
class MediosPagoTarifafijaClientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'medio_pago_id', 'valormaximo', 'valor_comision_cliente', 'estado'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function clientes()
    {
        return $this->belongsTo('Clientes', 'cliente_id', 'Id');
    }
}
