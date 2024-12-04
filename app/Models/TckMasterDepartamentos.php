<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckMasterDepartamentos
 * @package App\Models
 */
class TckMasterDepartamentos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_masterdepartamentos';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre',
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