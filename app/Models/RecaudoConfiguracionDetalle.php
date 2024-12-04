<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_recaudo_configuracion_general
 * @property string $valor
 */
class RecaudoConfiguracionDetalle extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_configuracion_detalle';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_recaudo_configuracion_general',
        'valor',
        ];

    /**
     * @var array
     */
    protected $dates = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
