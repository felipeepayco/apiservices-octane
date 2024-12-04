<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClientesTerminos
 * @package App\Models
 * @property int $id
 * @property int $client_id
 * @property bool $charge_enable
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ConfAlliedBilling extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config_allied_billing';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'client_id',
        'charge_enable',
        'created_at',
        'updated_at'
    ];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at' ];

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