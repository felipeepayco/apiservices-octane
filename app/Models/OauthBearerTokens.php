<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $session_id
 * @property int $expire_time
 * @property int $cliente_id
 * @property \Carbon\Carbon  $created_at
 * @property \Carbon\Carbon  $updated_at

 * @property string $codigo_dian
 */
class OauthBearerTokens extends Model
{

    protected $primaryKey = 'id'; // or null
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * @var array
     */
    protected $fillable = ['id','session_id', 'expire_time','cliente_id'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $dates = ['created_at', 'updated_at'];


}
