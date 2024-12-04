<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $nombres
 * @property string $apellidos
 * @property string $documento
 * @property int $tipo_doc
 * @property int $digito
 * @property bool restricted_user
 */
class ClientesSocios extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_socios';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'nombres', 'apellidos', 'documento', 'tipo_doc', 'restricted_user','digito'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
