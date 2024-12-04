<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientesTerminos
 * @package App\Models
 * @property int $id
 * @property int $id_cliente
 * @property int $id_termino
 * @property string $version
 * @property bool $acepto
 * @property int $id_preregistro
 * @property \Carbon\Carbon $fecha
 */
class ClientesTerminos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_terminos';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_cliente',
        'id_termino',
        'version',
        'acepto',
        'id_preregistro',
        'fecha',
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;
}