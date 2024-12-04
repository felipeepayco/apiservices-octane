<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PasarelaConfig
 * @package App\Models
 * @property int $id
 * @property string $parametro
 * @property string $valor
 */
class PasarelaConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pasarela_config';

    /**
     * @var array
     */
    protected $fillable = [
        'parametro',
        'valor',
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
