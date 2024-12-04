<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property int $config_id
 * @property string $valor
 */
class DetalleConfClientes extends Model
{

   protected $table = 'detalle_conf_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id','config_id', 'valor'];

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

    public function configuracion(){
        return $this->belongsTo(ConfClientes::class,'config_id');
    }

}
