<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckEstadosRespuestas
 * @package App\Models
 */
class TckEstadosRespuestas extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_estadosrespuestas';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre'
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