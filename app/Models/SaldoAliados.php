<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @package App\Models
 * @property int $id
 * @property float $saldo_aliado
 * @property float $saldo_disponible
 * @property float $saldo_retenido
 * @property int $minimo_retiro
 * @property int $aliado_id
 * @property int $alianza_id
 */
class SaldoAliados extends Model
{
    /**
     * @var string
     */
    protected $table = 'saldo_aliados';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'saldo_aliado',
        'saldo_disponible',
        'saldo_retenido',
        'minimo_retiro',
        'aliado_id',
        'alianza_id',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     */
    public $timestamps = false;
}