<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre_red
 * @property int $id_confgapi
 * @property string $proveedor
 */
class TrConfigapiRed extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'tr_configapi_red';

    /**
     * @var array
     */
    protected $fillable = ['nombre_red', 'id_confgapi', 'proveedor', 'enabled'];

    /**
     * @var int
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
