<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property float $comision_franquicias
 * @property float $comision_tr_credito
 * @property float $comision_tr_pse
 * @property float $comision_tr_presencial
 * @property float $comision_retiro
 * @property float $valor_mensual
 * @property int $cliente_id
 * @property int $estado


 * @property string $porcentaje
 */
class PlanFijoClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_fijo_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'valor_mensual', 'comision_franquicias', 'comision_tr_credito','comision_tr_pse','comision_tr_presencial','comision_retiro','cliente_id','estado'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
