<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $descripcion
 */
class TipoDocumentos extends Model
{
    /**
     * @var array
     */
    protected $table = "tipo_documentos";

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo', 'id_conf_pais', 'validacion'];

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

    public static function findByCountryAndType($country = 1)
    {
        $instance = new self();

        return $qb = $instance
            ->select("tipo_documentos.*")
            ->where("id_conf_pais",$country)
            ->where("persona", 1)
            ->orWhere("empresa", 1)
            ->get();

    }

}
