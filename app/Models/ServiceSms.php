<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ServiceSms
 *
 * @package App\Models
 */
class ServiceSms extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sms_services';

    /**
     * @var array
     */
    protected $fillable = [
        'provider',
        'client_id',
        'transaction_id',
        'recipient',
        'message',
        'extras',
        'request',
        'response',
    ];


    /**
     * @var array
     */
    protected $dates = [
        'created_at'
    ];
}
