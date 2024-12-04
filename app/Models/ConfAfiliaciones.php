<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property float $valor
 */
class ConfAfiliaciones extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'valor'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
