<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientsRestrictiveListTypeValidation
 * @package App\Models
 * @property int $id
 * @property string $tipo_validacion
 */
class ClientsRestrictiveListTypeValidation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_lista_restrictiva_tipo_validacion';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'tipo_validacion',
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
