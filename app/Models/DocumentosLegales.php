<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int cliente_id
 * @property int $tipo_doc
 * @property string $nombre
 * @property \Carbon\Carbon $fecha_creacion
 * @property string extension
 * @property string subido
 * @property string $aprobado
 * @property string $usuario_id
 * @property string $url
 * @property int $respuesta_id
 * @property string $observaciones
 * @property int $bancaria_id

 */
class DocumentosLegales extends Model
{

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'documentos_legales';

    /**
     * @var array
     */
    protected $fillable = ['cliente_id', 'tipo_doc', 'nombre','fecha_creacion','extension','subido','aprobado','usuario_id','url','respueta_id','observaciones','bancaria_id'];

    protected $dates = ['fecha_creacion'];

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
