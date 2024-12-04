<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @package App\Models
 * @property int $id
 * @property \Carbon\Carbon $fecha
 * @property string $nombre
 * @property string $url
 * @property int $comision
 * @property int $minimo_retiro
 */
class ConfiguracionAlianzaAliados extends Model
{
    /**
     * @var string
     */
    protected $table = 'configuracion_alianza_aliados';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha',
        'nombre',
        'url',
        'comision',
        'minimo_retiro'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * @var bool
     */
    public $timestamps = false;
}