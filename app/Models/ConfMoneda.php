<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $moneda
 * @property string $cod_moneda
 * @property int $decimales
 * @property string $separador
 */
class ConfMoneda extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'moneda',
        'cod_moneda',
        'decimales',
        'separador'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conf_moneda';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}