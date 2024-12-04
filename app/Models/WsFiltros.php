<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $cod_respuesta
 * @property string $descripcion
 * @property string $filtro
 * @property string $funcion
 * @property string $medios_pago
 * @property int $scoring
 * @property int $obligatorio
 * @property int $orden
 * @property int $activo
 * @property-read WsFiltrosClientes[] $wsFiltrosClientes
 * @property-read WsFiltrosDefault[] $wsFiltrosDefaults
 * @property-read WsFiltrosPais[] $wsFiltrosPais
 */
class WsFiltros extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'cod_respuesta', 'descripcion', 'filtro',
        'funcion', 'medios_pago', 'scoring','obligatorio','orden','activo'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsFiltrosClientes()
    {
        return $this->hasMany('WsFiltrosClientes', 'filtro');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsFiltrosDefaults()
    {
        return $this->hasMany('WsFiltrosDefault', 'filtro');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsFiltrosPais()
    {
        return $this->hasMany('WsFiltrosPais', 'filtro_id');
    }
}
