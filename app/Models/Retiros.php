<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Retiros
 * @package App\Models
 */
class Retiros extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'retiros';

    /**
     * @var array
     */
    protected $fillable = [
        'Id',
        'id_cliente',
        'fecharetiro',
        'fechapago',
        'saldoanterior',
        'dinerosolicitado',
        'comision',
        'iva',
        'cuatro_por_mil',
        'saldodisponible',
        'saldorestante',
        'estado',
        'destino_id',
        'tipo',
        'invoice_id'
    ];


    /**
     * @var array
     */
    protected $dates = ['fecharetiro', 'fechapago'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
