<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id_cliente
 * @property string $descripcion
 * @property string $detalle
 * @property string $referencia
 * @property string $valor
 * @property string $moneda
 * @property string $tax
 * @property string $amount_base
 * @property string $url_respuesta
 * @property string $url_confirmacion
 * @property string $url_imagen
 * @property string $url_imagenexterna
 * @property int $tipo
 * @property float $ico
 */

class BotonesPago extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'botones_pago';

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * @var array
     */
    protected $fillable = ['id_cliente', 'descripcion', 'detalle','referencia','valor',
        'moneda','tax','amount_base','url_respuesta','url_confirmacion','url_imagen',
        'url_imagenexterna', 'tipo', 'ico'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}