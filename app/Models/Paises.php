<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo_pais
 * @property string $nombre_pais
 * @property string $indicativo
 */
class Paises extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['codigo_pais', 'nombre_pais', 'indicativo'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
