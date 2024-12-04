<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserCuenta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_cuenta';

    /**
     * @var array
     */
    protected $fillable = ['id', 'grant_user_id','cliente_id','estado','nombre_cuenta','cliente_id_duplicado'];

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

