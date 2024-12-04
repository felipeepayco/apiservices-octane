<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ModalClientes
 * @package App\Models
 * @property int $id
 * @property int $cliente_id
 * @property int $modal_config_id
 * @property int $contador
 * @property int $no_ver_mas
 * @property \Carbon\Carbon fecha
 * @property int $log_usuario_id
 */
class ModalClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modal_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'modal_config_id', 'contador', 'no_ver_mas', 'fecha', 'log_usuario_id'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
