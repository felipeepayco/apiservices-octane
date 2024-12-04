<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property int $redsocial_id
 * @property string $url
 */
class ClientesRedesSociales extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes_redessociales';

    /**
     * @var array
     */
    protected $fillable = ['id', 'redsocial_id', 'cliente_id', 'url'];

      /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
