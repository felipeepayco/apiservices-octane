<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $urlpruebas
 * @property string $wsdlpruebas
 * @property string $wsdlproduccion
 * @property string $urlproduccion
 * @property string $wsdl_cancelacion_pruebas
 * @property string $wsdl_cancelacion_produccion
 * @property string $wsdl_consulta_pruebas
 * @property string $wsdl_consulta_produccion
 * @property string $acquirerId
 * @property string $rootcertificados
 * @property string $transactionTrace
 * @property string $language
 * @property string $certificado_encrypt
 * @property string $username
 * @property int $password
 * @property string $url_autentication
 */
class TrConfigapi extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tr_configapi';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'urlpruebas',
        'wsdlpruebas',
        'wsdlproduccion',
        'urlproduccion',
        'wsdl_cancelacion_pruebas',
        'wsdl_cancelacion_produccion',
        'wsdl_consulta_pruebas',
        'wsdl_consulta_produccion',
        'acquirerId',
        'rootcertificados',
        'transactionTrace',
        'language',
        'certificado_encrypt',
        'username',
        'password',
        'url_autentication'
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha_actualizacion', 'fecha_creacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
