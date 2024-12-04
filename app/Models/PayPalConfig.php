<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $secret
 * @property string $api_base_url
 * @property string $parthner_receiver
 * @property string $descripcion
 */
class PayPalConfig extends Model
{

    protected $table = 'paypal_config';

    /**
     * @var array
     */
    protected $fillable = [
        'cliente_id',
        'secret',
        'api_base_url',
        'parthner_receiver',
        'descripcion',
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
