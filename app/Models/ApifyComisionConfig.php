<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ApifyComisionConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'apify_comision_config';

    /**
     * @var array
     */
    protected $fillable = ['id', 'id_cliente', 'nombre', 'descripcion','porcentaje','fijo','value','codigo',"limite_mensual","limite_transaccion","canal","app_id"];

}
