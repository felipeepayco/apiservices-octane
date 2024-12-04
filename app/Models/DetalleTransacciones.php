<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $Id
 * @property string $det_cliente
 * @property string $tipo_doc
 * @property string $cedula
 * @property \Carbon\Carbon $fecha_exp
 * @property string $nombres
 * @property string $apellidos
 * @property string $compania
 * @property string $direccion
 * @property string $celular
 * @property string $telefono
 * @property string $fax
 * @property string $ext
 * @property string $pais
 * @property string $ciudad
 * @property string $estado
 * @property string $codigo_pais
 * @property string $codigo_area
 * @property string $pago
 * @property string $emaild
 * @property string $zip
 * @property int $numerotarjeta
 * @property int $cuotas
 */
class DetalleTransacciones extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['Id', 'det_cliente', 'tipo_doc', 'cedula',
        'fecha_exp', 'nombres', 'apellidos', 'compania', 'direccion', 'celular',
        'telefono', 'fax', 'ext', 'pais', 'ciudad', 'estado', 'codigo_pais',
        'codigo_area', 'pago', 'emaild', 'zip', 'numerotarjeta', 'cuotas'];

    /**
     * @var array
     */
    protected $dates = ['fecha_exp'];

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
