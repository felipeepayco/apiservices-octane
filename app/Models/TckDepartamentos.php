<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckDepartamentos
 * @package App\Models
 */
class TckDepartamentos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_departamentos';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre',
        'masterdepartamentos_id'
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function masterDepartamentos()
    {
        return $this->belongsTo(TckMasterDepartamentos::class, 'masterdepartamentos_id');
    }
}