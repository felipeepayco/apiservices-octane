<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $ip
 * @property string $response
 */
class LogGeoip extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_geoip';

    /**
     * @var array
     */
    protected $fillable = ['id', 'response','ip'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

}
