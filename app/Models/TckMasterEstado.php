<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckMasterDepartamentos
 * @package App\Models
 */
class TckMasterEstado extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_masterestado';

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