<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre_var
 * @property int $default_value
 */
class ConfClientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'nombre_var', 'default_value'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    public function detalle(){
        return $this->hasMany(DetalleConfClientes::class,'config_id');
    }

}
