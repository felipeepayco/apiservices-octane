<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $banco
 * @property mixed $comision_presencial
 * @property mixed $comision_pse
 * @property mixed $comision_retiro
 * @property mixed $comision_transaccion_presencial
 * @property mixed $comision_transaccion_pse
 * @property mixed $comisionamerican
 * @property mixed $comisiondiners
 * @property mixed $comisionmaster
 * @property mixed $comisiontransaccion
 * @property mixed $comisionvisa
 * @property int $Id
 * @property int $id_cliente
 * @property string $ncuenta
 * @property int $porcentaje_reserva
 * @property mixed $saldo_cliente
 * @property mixed $saldo_disponible
 * @property mixed $saldo_reserva
 * @property mixed $saldo_retenido
 * @property int $tipocuenta
 * @property int $cuatro_por_mil
 * @property int $v_checkout
 * @property string $clasificacion_dian
 * @property int ica_region
 * @property int ica_ciudad
 * @property string $clasificacion_regimen
 */
class DetalleClientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['banco', 'comision_presencial', 'comision_pse', 'comision_retiro',
        'comision_transaccion_presencial', 'comision_transaccion_pse', 'comisionamerican',
        'comisiondiners', 'comisionmaster', 'comisiontransaccion', 'comisionvisa', 'Id',
        'id_cliente', 'ncuenta', 'porcentaje_reserva', 'saldo_cliente', 'saldo_disponible',
        'saldo_reserva', 'saldo_retenido', 'tipocuenta', 'titular','cuatro_por_mil','v_checkout',
        'clasificacion_dian', 'ica_region'. 'ica_ciudad', 'clasificacion_regimen'];

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

}
