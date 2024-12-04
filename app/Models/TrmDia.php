<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TrmDia extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trm_dia';

    /**
     * @var array
     */
    protected $fillable = ['id', 'fecha','valor'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
