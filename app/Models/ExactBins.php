<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 */
class ExactBins extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exact_bins';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'bin_tarjeta',
        'franquicia',
        'banco',
        'tipo',
        'sub_tipo',
        'codigo_pais_iso_a2',
        'codigo_pais_iso_a3',
        'codigo_pais_iso_num',
        'created_at',
        'updated_at',
        'pais'
    ];


    /**
     * @var array
     */
    protected $dates = ['created_at','updated_at'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
