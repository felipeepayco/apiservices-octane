<?php

namespace App\Models\V2;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ForbiddenWordLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'forbiddenWordLogs';
    protected $primaryKey = 'id';
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['id',
        'modulo',
        'accion',
        'cliente_id',
        'palabra'
    ];

}
