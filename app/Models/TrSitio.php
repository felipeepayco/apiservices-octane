<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PreRegister
 * @package App\Models
 * @property $id
 * @property $commerceId
 * @property $codigounico
 * @property $terminalcode
 * @property $Clavecertificado
 * @property $email_notificaciones
 * @property $cliente_id
 * @property $certificado_txt
 * @property $certificadokey_txt
 * @property $red
 * @property $cvv
 * @property $username
 * @property $password
 * @property $id_config_api
 * @property $nombresitio
 * @property $enpruebas
 * @property $estado
 * @property $fecha_eliminacion
 */

class TrSitio extends Model
{
    protected $table = 'tr_sitio';

    protected $fillable = [
        'commerceId',
        'codigounico',
        'terminalcode',
        'Clavecertificado',
        'email_notificaciones',
        'cliente_id',
        'certificado_txt',
        'certificadokey_txt',
        'cvv',
        'red',
        'username',
        'password',
        'id_config_api',
        'nombresitio',
        'enpruebas',
        'estado',
        'fecha_eliminacion',
    ];

    protected $primaryKey = 'id';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $hidden = [];
}