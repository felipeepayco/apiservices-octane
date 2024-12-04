<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tipo_comision
 * @property int $valor_comision
 * @property int $aliado_id
 * @property int $cliente_id
 * @property string $porcentaje
 */
class ComisionClienteAliado extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comision_cliente_aliado';

    /**
     * @var array
     */
    protected $fillable = ['id', 'tipo_comision', 'valor_comision', 'aliado_id','cliente_id'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
