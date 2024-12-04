<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo_secundario_recaudo_facturas
 * @property string $tipo_doc
 * @property string $documento
 * @property string $nombres
 * @property string $apellidos
 * @property string $pais
 * @property string $estado
 * @property string $ciudad
 * @property string $direccion
 * @property string $telefono
 * @property string $email
 * @property bool $borrado
 * @property string $identificacion_empresa
 * @property int $id_recaudo_facturas_lote
 */
class RecaudoFacturasClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_factura_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'codigo_secundario_recaudo_facturas', 'tipo_doc', 'documento',
        'nombres', 'apellidos', 'pais', 'estado', 'ciudad', 'direccion','telefono','email','borrado','identificacion_empresa','id_recaudo_facturas_lote'];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
