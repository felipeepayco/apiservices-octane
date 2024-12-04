<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 */
class ProductosEstados extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'descripcion'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
