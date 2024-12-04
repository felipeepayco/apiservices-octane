<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Regions extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'ID',
        'country',
        'code   ',
        'url',
        'name',
        'latitude',
        'longitude',
        'cities',
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
