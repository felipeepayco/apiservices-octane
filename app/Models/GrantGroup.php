<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $roles
 * @property int $cliente_id
 */

class GrantGroup extends Model
{
    protected $table = 'grant_group';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'roles',
        'cliente_id'
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
