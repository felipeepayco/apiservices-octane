<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $codigo
 * @property string $configuracion
 */
class TipoProducto extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipo_producto';

    /**
     * @var array
     */
    protected $fillable = [
        'nombre',
        'codigo',
        'configuracion'
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