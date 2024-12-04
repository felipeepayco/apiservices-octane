<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $moneda
 * @property int $decimales
 * @property float $fecha_creacion
 * @property string $zona_horaria
 * @property string $separador
 * @property int $moneda_nacional
 * @property int $aliados
 */
class ConfPais extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'moneda',
        'decimales',
        'cod_pais',
        'fecha_creacion',
        'zona_horaria',
        'separador',
        'moneda_nacional',
        'aliados'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conf_pais';

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}