<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $data
 * */
class LogElasticRecaudoFacturas extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'data'];

    /**
     * @var array
     */
//    protected $dates = ['fechafin', 'fechainicio'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'log_elastic_recaudo_facturas';

}
