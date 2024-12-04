<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $perfil_id
 * @property int $validacion_id
 * @property string $porcentaje
 */
class LimPerfilesValidacion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lim_perfiles_validacion';

    /**
     * @var array
     */
    protected $fillable = ['id', 'perfil_id', 'validacion_id', 'porcentaje'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
