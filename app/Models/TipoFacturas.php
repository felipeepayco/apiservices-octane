<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 */
class TipoFacturas extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['nombre', 'descripcion'];

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