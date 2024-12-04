<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $daviplata
 * @property int $amount
 */


class DaviplataHistoryAmount extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'daviplata_history_amount';

    /**
     * @var array
     */
    protected $fillable = ['daviplata', 'amount','id','tipo'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


}
