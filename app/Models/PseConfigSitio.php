<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PseConfigSitio
 * @package App\Models
 * @property $id
 * @property $cliente_id
 * @property $entity_code
 * @property $service_code
 * @property $gateway_id
 */
class PseConfigSitio extends Model
{
    protected $table = 'pse_config_sitio';

    protected $fillable = [
        'cliente_id',
        'entity_code',
        'service_code',
        'gateway_id',
    ];

    public $timestamps = false;
}