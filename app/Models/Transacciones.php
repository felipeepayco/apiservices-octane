<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $Id
 * @property string $id_factura
 * @property int $id_cliente
 * @property \Carbon\Carbon $fecha
 * @property \Carbon\Carbon $fechaexpiracion
 * @property string $tarjeta
 * @property string $moneda
 * @property mixed $dolares
 * @property mixed $valortotal
 * @property mixed $valorneto
 * @property mixed $iva_cliente
 * @property mixed $base_iva_cliente
 * @property mixed $comision_banco
 * @property mixed $comision_tarjeta
 * @property mixed $iva_comision_tarjeta
 * @property mixed $retefuente
 * @property mixed $reteiva
 * @property mixed $comision_transaccion
 * @property mixed $iva_transaccion
 * @property mixed $iva_lineapagos
 * @property mixed $ganancia_lineapagos
 * @property mixed $ganancia_cliente
 * @property string $estado
 * @property string $franquicia
 * @property string $nombre_banco
 * @property string $respuesta
 * @property string $autorizacion
 * @property string $recibo
 * @property int $cobro_id
 * @property string $descripcion_producto
 * @property string $ip_transaccion
 * @property int $enpruebas
 * @property string $cod_respuesta
 * @property string $urlrespuesta
 * @property string $confirmenviada
 * @property string $extras
 * @property string $metodoconfirmacion
 * @property string $urlconfirmacion
 * @property mixed $trmdia
 * @property string $pin
 */
class Transacciones extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transacciones';

    /**
     * @var array
     */
    protected $fillable = ['Id', 'id_factura', 'id_cliente', 'fecha',
        'fechaexpiracion', 'tarjeta', 'moneda', 'dolares', 'valortotal',
        'valorneto', 'iva_cliente', 'base_iva_cliente', 'comision_banco',
        'comision_tarjeta', 'iva_comision_tarjeta', 'retefuente', 'reteiva', 
        'comision_transaccion', 'iva_transaccion', 'iva_lineapagos', 'cobro_id',
        'ganancia_lineapagos', 'ganancia_cliente', 'estado', 'franquicia', 
        'nombre_banco', 'respuesta', 'autorizacion', 'recibo', 
        'descripcion_producto', 'ip_transaccion', 'enpruebas', 'cod_respuesta',
        'urlrespuesta', 'trmdia', 'confirmenviada','extras','metodoconfirmacion',
        'urlconfirmacion','pin'];

    /**
     * @var array
     */
    protected $dates = ['fecha', 'fechaexpiracion'];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cobro()
    {
        return $this->belongsTo(Cobros::class, 'cobro_id', 'Id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente', 'Id');
    }

    public function isProductionAccepted() : bool
    {
        return $this->estado == 'Aceptada' &&
            $this->enpruebas != 1 && ($this->autorizacion > 0 || $this->autorizacion != '000000');
    }


}
