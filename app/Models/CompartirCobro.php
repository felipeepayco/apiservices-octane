<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_cliente
 * @property string $documento
 * @property string $nombre
 * @property string telefono
 * @property string $ext
 * @property string $celular
 * @property string $tipo_contacto
 * @property int $tipo_doc
 * @property \Carbon\Carbon $fecha_exp




 * @property string $porcentaje
 */
class CompartirCobro extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'compartircobro';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha', 'mensaje','tipoenvio','valor','cobro_id'];

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
