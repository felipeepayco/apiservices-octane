<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $cliente_id
 * @property \Carbon\Carbon $fecha
 * @property string $txtcodigo
 * @property string $rutaqr
 * @property string $titulo
 * @property string $numerofactura
 * @property string $descripcion
 * @property string $moneda
 * @property float $valor
 * @property float $iva
 * @property float $base_iva
 * @property float $precio_descuento
 * @property int $cobrounico
 * @property int $cantidad
 * @property float $disponible
 * @property float $estado
 * @property \Carbon\Carbon $fecha_expiracion
 * @property string $url_respuesta
 * @property string $url_confirmacion
 * @property int $tipocobro
 * @property float $ico
 */

class Cobros extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cobros';

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * @var array
     */
    protected $fillable = ['cliente_id', 'fecha', 'txtcodigo','rutaqr','titulo','numerofactura',
        'descripcion','moneda','valor','iva','base_iva','precio_descuento','cobrounico','cantidad',
        'disponible','estado','fecha_expiracion','url_respuesta','url_confirmacion','tipocobro', 'ico'];

    /**
     * @var array
     */
    protected $dates = ['fecha','fecha_expiracion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente_id', 'Id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transacciones()
    {
        return $this->hasMany(Transacciones::class, 'cobro_id');
    }

    public function getPendingTransactions()
    {
        return Transacciones::where("estado", "Pendiente")
            ->where("cobro_id", $this->Id)
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cobrosLogTransacciones()
    {
        return $this->hasMany('App\Models\CobrosLogTransacciones', 'cobro_id');
    }
}
