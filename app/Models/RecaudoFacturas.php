<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_cliente
 * @property \Carbon\Carbon $fecha
 * @property string $factura_id
 * @property \Carbon\Carbon $fecha_factura
 * @property \Carbon\Carbon $fecha_expiracion
 * @property string $descripcion
 * @property string $moneda
 * @property double $subtotal
 * @property double $iva
 * @property double $total
 * @property string $extras
 * @property string $estado_pago
 * @property float $valor_restante
 * @property int $id_recaudo_facturas_lote
 * @property int $envio_completo
 * @property int $error
 * @property bool $borrado
 * @property string $log_error
 * @property bool $pago_tercero
 * @property string $estado_transaccion
 * @property float $segundo_total
 * @property float $segundo_iva
 * @property \Carbon\Carbon $segunda_fecha_vencimiento
 * @property string $segunda_descripcion
 * @property float $tercer_total
 * @property float $tercer_iva
 * @property \Carbon\Carbon $tercera_fecha_vencimiento
 * @property string $tercera_descripcion
 * @property string $codigo_servicio
 * @property bool $pago_recurrente
 * @property bool $manual
 * @property string $codigo_secundario
 * @property int $referencia_transaccion
 */
class RecaudoFacturas extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_facturas';

    /**
     * @var array
     */
    protected $fillable = ['id', 'id_cliente', 'fecha', 'factura_id',
        'fecha_factura', 'fecha_expiracion', 'descripcion', 'moneda',
        'subtotal', 'iva','total','extras','estado_pago','valor_restante',
        'id_recaudo_facturas_lote','envio_completo','error','borrado','log_error',
        'pago_tercero','estado_transaccion','segundo_total','segundo_iva','segunda_fecha_vencimiento',
        'segunda_descripcion','tercer_total','tercer_iva','tercera_fecha_vencimiento','tercera_descripcion',
        'codigo_servicio','pago_recurrente','manual','codigo_secundario','referencia_transaccion'];

    /**
     * @var array
     */
    protected $dates = ['fecha', 'fecha_factura','fecha_expiracion','segunda_fecha_vencimiento','tercera_fecha_vencimiento'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
