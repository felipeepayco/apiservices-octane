<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $codigo_responsabilidad_fiscal
 * @property string $tipo_persona
 * @property string $regimen_iva
 * @property string $sub_responsabilidad
 * @property string $clasificacion
 */
class ResponsabilidadFiscal extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'responsabilidad_fiscal';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'codigo_responsabilidad_fiscal',
        'tipo_persona',
        'regimen_iva',
        'sub_responsabilidad',
        'clasificacion'
        ];

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
