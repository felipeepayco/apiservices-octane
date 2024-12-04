<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $url
 * @property int $tipo
 * @property \Carbon\Carbon $fechacreacion
 * @property int $cobro_id
 */
class FilesCobro extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filescobro';

    /**
     * @var array
     */
    protected $fillable = ['id', 'nombre', 'url', 'tipo','fechacreacion','cobro_id'];

    /**
     * @var array
     */
    protected $dates = ['fechacreacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
