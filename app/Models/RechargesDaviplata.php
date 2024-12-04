<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RechargesDaviplata extends Model
{
    protected $table = 'recargas_daviplata';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'terminal',
        'email'
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
