<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $comision
 * @property mixed $comision_cliente
 * @property string $Id
 * @property string $nombre
 * @property int $valor_comision_cliente
 * @property int $activo
 */
class MediosPago extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'medios_pago';

    /**
     * @var array
     */
    protected $fillable = ['comision', 'comision_cliente', 'Id', 'nombre', 'valor_comision_cliente', 'activo', 'tipo'];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
