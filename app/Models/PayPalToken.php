<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $token
 * @property string $tipo
 * @property string $vencimiento
 * @property string $response
 */
class PayPalToken extends Model
{

    protected $table = 'paypal_token';

    /**
     * @var array
     */
    protected $fillable = [
        'token',
        'tipo',
        'response',
    ];

    /**
     * @var array
     */
    protected $dates = ['vencimiento'];

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
