<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string id_departamento
 * @property string $nombre
 * @property string $indicativo
 */
class Municipios extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id_departamento', 'nombre', 'indicativo'];

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
