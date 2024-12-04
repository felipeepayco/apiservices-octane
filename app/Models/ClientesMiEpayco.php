<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientesTerminos
 * @package App\Models
 * @property int $id
 * @property int $id_cliente
 * @property string $email
 * @property string $imagen_perfil
 * @property string $imagen_fondo
 * @property int $telefono
 * @property int $ind_pais
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_actualizacion
 * @property int $estado
 */
class ClientesMiEpayco extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_mi_epayco';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_id',
        'email',
        'imagen_perfil',
        'imagen_fondo',
        'telefono',
        'ind_pais',
        'fecha_creacion',
        'fecha_actualizacion',
        'estado'
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion','fecha_actualizacion' ];

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