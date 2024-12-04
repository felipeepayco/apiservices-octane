<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_oauth
 * @property string $full_name
 * @property string $given_name
 * @property string $family_name
 * @property string $image_url
 * @property string $email
 * @property string $token
 * @property int $id_cliente
 * @property string $networks_social
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GrantUserOauth extends Model
{
    protected $table = 'grant_user_oauth';

    /**
     * @var array
     */
    protected $fillable = [
        'id_oauth',
        'full_name',
        'given_name',
        'famlity_name',
        'image_url',
        'email',
        'token',
        'id_cliente',
        'networks_social'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $dates = ['created_at', 'updated_ad'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
