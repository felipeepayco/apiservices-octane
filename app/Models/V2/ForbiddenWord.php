<?php

namespace App\Models\V2;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ForbiddenWord extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'forbiddenWords';
    protected $primaryKey = 'id';
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['id',
        'nombre',
    ];

}
