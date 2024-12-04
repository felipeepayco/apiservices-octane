<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_cliente
 * @property \Carbon\Carbon $fecha
 * @property \Carbon\Carbon $fecha_actualizacion
 * @property string $nombre
 * @property string $nombre_archivo
 * @property string $conf_franquicias
 * @property string $url_copia
 * @property \Carbon\Carbon $fecha_generacion
 * @property \Carbon\Carbon $hora_generacion
 * @property \Carbon\Carbon $fecha_cron_inicio
 * @property \Carbon\Carbon $fecha_cron_fin
 * @property int $total_registros
 * @property int $total_importados
 * @property int $total_generados
 * @property int $total_errores
 * @property int $ultima_linea
 * @property int $importacion_completa
 * @property int $generacion_completa
 * @property bool $borrado
 * @property bool $opcion_cliente
 * @property int $configuracion_importacion_id
 * @property int $configuracion_general_id
 * @property double $valor_lote
 */
class RecaudoFacturasLote extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_facturas_lote';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_cliente',
        'fecha',
        'fecha_actualizacion',
        'nombre',
        'nombre_archivo',
        'conf_franquicias',
        'url_archivo',
        'url_copia',
        'fecha_generacion',
        'hora_generacion',
        'fecha_cron_inicio',
        'fecha_cron_fin',
        'total_registros',
        'total_importados',
        'total_generados',
        'total_errores',
        'ultima_linea',
        'importacion_completa',
        'generacion_completa',
        'envio_completo',
        'borrado',
        'opcion_cliente',
        'configuracion_importacion_id',
        'configuracion_general_id',
        'valor_lote',
        ];

    /**
     * @var array
     */
    protected $dates = [
        'fecha',
        'fecha_factura',
        'fecha_actualizacion',
        'fecha_generacion',
        'hora_generacion',
        'fecha_cron_inicio',
        'fecha_cron_fin',
        'fecha_cron_fin',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
