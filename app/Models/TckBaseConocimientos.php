<?php


namespace App\Models;


/**
 * Class TckBaseConocimientos
 * @package App\Models
 */
class TckBaseConocimientos
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_baseconocimientos';

    /**
     * @var array
     */
    protected $fillable = [
        'titulo',
        'texto',
        'autor',
        'incidencias_id'
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
    public $timestamps = true;

    public function Incidencias()
    {
        return $this->belongsTo(TckIncidencias::class, 'incidencias_id');
    }
}