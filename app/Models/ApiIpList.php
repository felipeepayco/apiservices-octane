<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $idCliente
 * @property int $ip
 * @property string $descripcion
 */


class ApiIpList extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'api_ip_list';

    /**
     * @var array
     */
    protected $fillable = ['id', 'id_cliente', 'ip', 'decripcion'];

    /**
     * @var array
     */
    protected $protectedIpFilter = ['id', 'id_cliente'];


}
