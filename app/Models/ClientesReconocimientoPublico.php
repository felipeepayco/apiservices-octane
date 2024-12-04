<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $reconocimiento_publico
 * @property string $rol
 * @property string $cargo
 * @property \Carbon\Carbon $fecha_vinculacion
 * @property \Carbon\Carbon $fecha_finalizacion
 * @property boolean $ejerciendo
 * @property integer $id_cliente
 */
class ClientesReconocimientoPublico extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_reconocimiento_publico';

    /**
     * @var array
     */
    protected $fillable = ['id', "reconocimiento_publico","rol","cargo","fecha_vinculacion","fecha_finalizacion","ejerciendo",'id_cliente'];

    /**
     * @var array
     */
    protected $dates = ['fecha_vinculacion', 'fecha_finalizacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
