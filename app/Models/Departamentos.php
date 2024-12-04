<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo_dian
 * @property string $nombre
 * @property string $indicativo
 */
class Departamentos extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['codigo_dian', 'nombre', 'indicativo'];

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
