<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $grant_user_id
 * @property int $grant_group_id
 */
class GrantUserGrantGroup extends Model
{
    protected $table = 'grant_user_grant_group';

    /**
     * @var array
     */
    protected $fillable = [
        'grant_user_id',
        'grant_group_id'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
