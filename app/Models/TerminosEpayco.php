<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TerminosEpayco
 * @package App\Models
 * @property int $id
 * @property string $nombre
 * @property string $url
 * @property string $version
 * @property \Carbon\Carbon $fecha
 */
class TerminosEpayco extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'terminos_epayco';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'url',
        'version',
        'fecha',
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;
}