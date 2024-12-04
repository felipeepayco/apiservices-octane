<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 * @property string $pais
 * @property string $tipocliente
 * @property boolean $predeterminado
 * @property boolean $persona
 * @property boolean $comercio
 * @property boolean $gateway
 * @property boolean $documento_adicional
 * @property int $limite_id
 */
class TipoDocumentosLegales extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['nombre', 'descripcion', 'pais', 'tipocliente', 'predeterminado', 'persona', 'comercio', 'gateway', 'documento_adicional', 'limite_id'];

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
