<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_cliente
 * @property int $id_responsabilidad_fiscal
 */
class ResponsabilidadFiscalClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'responsabilidad_fiscal_clientes';

    /**
     * @var array
     */
    protected $fillable = [
        'id_cliente',
        'id_responsabilidad_fiscal'
        ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
