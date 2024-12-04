<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_cliente
 * @property string $nombre
 * @property string $tipo_recaudo
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_ultima_modificacion
 * @property string $url
 * @property string $nombre_empresa
 * @property string $tel_empresa
 * @property string $correo_empresa
 * @property string $archivo_prueba_url
 * @property string $archivo_prueba_url_copia
 * @property string $url_logo_empresa
 * @property string $estado
 * @property bool $completo
 * @property string $email_recaudo
 * @property int $tipo_archivo
 * @property bool $cabecera
 * @property string $separador
 * @property int $configuracion_importacion_id
 * @property int $configuracion_general_id
 * @property string $moneda
 * @property string $decimal_archivo
 * @property string $formato_fecha
 * @property string $tipo_tel
 * @property string $indicativo
 * @property bool $proviene_suscripcion
 */
class RecaudoProyecto extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recaudo_proyecto';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'id_cliente',
        'nombre',
        'tipo_recaudo',
        'fecha_creacion',
        'fecha_ultima_modificacion',
        'url',
        'nombre_empresa',
        'tel_empresa',
        'correo_empresa',
        'archivo_prueba_url',
        'archivo_prueba_url_copia',
        'url_logo_empresa',
        'estado',
        'completo',
        'email_recaudo',
        'tipo_archivo',
        'cabecera',
        'separador',
        'configuracion_importacion_id',
        'configuracion_general_id',
        'moneda',
        'decimal_archivo',
        'formato_fecha',
        'tipo_tel',
        'indicativo',
        'proviene_suscripcion',
        ];

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion', 'fecha_ultima_modificacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
