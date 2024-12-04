<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ApifyClientes
 * @package App\Models
 * @property int $id
 * @property int cliente_id
 * @property int $apify_cliente_id
 * @property \Carbon\Carbon fecha_creacion
 */
class ApifyClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'apify_clientes';

    /**
     * @var array
     */
    protected $fillable = [
        'apify_cliente_id',
        'cliente_id',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
