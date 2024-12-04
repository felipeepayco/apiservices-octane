<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoCodRespuestas
 * @package App\Models
 * @property $nombre
 */
class TipoCodRespuestas extends Model
{
    protected $table = 'tipo_cod_respuestas';

    protected $fillable = [
        'nombre',
    ];

    public $timestamps = false;
}