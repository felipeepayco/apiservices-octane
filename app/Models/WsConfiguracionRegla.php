<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WsConfiguracionRegla
 * @package App\Models
 * @property $id
 * @property $score_minimo
 * @property $score_maximo
 * @property $minutos_espera
 * @property $id_respuesta_cron
 */
class WsConfiguracionRegla extends Model
{
    protected $table = 'ws_configuracion_regla';

    protected $fillable = [
        'score_minimo',
        'score_maximo',
        'minutos_espera',
        'id_respuesta_cron',
    ];

    public $timestamps = false;
}