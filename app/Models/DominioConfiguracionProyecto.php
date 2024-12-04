<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 */
class DominioConfiguracionProyecto extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'nombre'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dominio_configuracion_proyectos';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
