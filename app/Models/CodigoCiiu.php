<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $codigo
 * @property string $descripcion
 * @property int $tipo
 * @property int $restringido
 */
class CodigoCiiu extends Model
{
    protected $table = 'codigo_ciiu';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'codigo',
        'descripcion',
        'tipo',
        'restringido'
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
