<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckFiles
 * @package App\Models
 */
class TckFiles extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_files';

    /**
     * @var array
     */
    protected $fillable = [
        'created_at',
        'respuestaticket_id',
        'nombre',
        'ruta',
        'base64'
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