<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GrantUser
 * @package App\Models
 */
class GrantUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grant_user';

    /**
     * @var array
     */
    protected $fillable = [
        'username',
        'nombres',
        'apellidos',
        'username_canonical',
        'email',
        'email_canonical',
        'enabled',
        'salt',
        'password',
        'last_login',
        'confirmation_token',
        'password_requested_at',
        'roles',
        'locked',
        'expired',
        'expires_at',
        'credentials_expire_at',
        'credentials_expired',
        'cliente_id',
        'borrado',
        'id_cliente_entidad_aliada',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
