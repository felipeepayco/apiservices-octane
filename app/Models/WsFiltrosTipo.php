<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WsFiltrosTipo
 * @package App\Models
 * @property $id
 * @property $nombre
 */
class WsFiltrosTipo extends Model
{
    protected $table = 'ws_filtro_tipo';

    protected $fillable = [
        'nombre',
    ];

    public $timestamps = false;
}