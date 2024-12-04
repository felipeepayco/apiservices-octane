<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $descripcion
 * @property float $comision_franquicias
 * @property float $comision_tr_credito
 * @property float $comision_tr_pse
 * @property float $comision_tr_presencial
 * @property float $comision_retiro
 * @property float $valor_mensual


 * @property string $porcentaje
 */
class ConfigPlanFijo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config_plan_fijo';

    /**
     * @var array
     */
    protected $fillable = ['id', 'descripcion', 'comision_franquicias', 'comision_tr_credito','comision_tr_pse','comision_tr_presencial','comision_retiro','valor_mensual'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
