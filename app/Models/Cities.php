<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Cities extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'ID',
        'country',
        'region',
        'url',
        'name',
        'latitude',
        'longitude',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
