<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $Id
 * @property mixed $trm_actual
 */
class Trm extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'trm';

    /**
     * @var array
     */
    protected $fillable = ['Id', 'trm_actual'];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
