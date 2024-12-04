<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $pais
 * @property float $valor
 * @property int $valor_base
 * @property string $codigo
 * @property string $descripcion
 * @property int $id_impuesto_siigo
 */
class ConfTaxes extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'pais',
        'valor',
        'valor_base',
        'codigo',
        'descripcion',
        'id_impuesto_siigo',
    ];
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}