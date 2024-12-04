<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $conf_pais_id
 * @property int $conf_moneda_id
 * @property bool $principal
 */
class PaisMoneda extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'conf_pais_id',
        'conf_moneda_id',
        'principal'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pais_moneda';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}