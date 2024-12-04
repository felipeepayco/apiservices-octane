<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientsTypeRisk
 * @package App\Models
 * @property int $id
 * @property string $usuario_lista
 */
class TypeRisk extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipo_riesgo';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'usuario_lista',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
