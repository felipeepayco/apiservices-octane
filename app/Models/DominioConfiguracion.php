<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $valor
 * @property boolean $estado
 * @property int $catalago_configuracion_tipo_id
 * @property int $cliente_id
 */
class DominioConfiguracion extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'valor', 'estado','id_dominio_configuracion_proyecto','cliente_id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dominio_configuracion';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
