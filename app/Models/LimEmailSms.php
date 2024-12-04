<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cliente_id
 * @property string $codigo_email
 * @property string $cod_sms
 * @property array $aprobado
 */
class LimEmailSms extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lim_email_sms';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_id',
        'codigo_email',
        'cod_sms',
        'abrobado'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
