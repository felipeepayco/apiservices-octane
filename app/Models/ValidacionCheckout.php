<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $error_code
 * @property string $error_message
 * @property string $error_description
 */
class ValidacionCheckout extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'validacion_checkout';

    /**
     * @var array
     */
    protected $fillable = ['id', 'error_code', 'error_message', 'error_description'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
