<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckIncidencias
 * @package App\Models
 */
class TckIncidencias extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_incidencias';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'prioridad_id'
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

    public function Prioridad()
    {
        return $this->belongsTo(TckPrioridad::class, 'prioridad_id');
    }
}