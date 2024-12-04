<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_payment_method
 * @property string $url
 */
class RedirectPaymentMethod extends Model
{
    protected $table = 'redirect_payment_method';

    /**
     * @var array
     */
    protected $fillable = [
        'id_payment_method',
        'url',
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
