<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BblDiscountCode extends Model
{
    protected $table = 'bbl_codigo_descuentos';
    protected $fillable=
    [
        "nombre",
        "tipo_descuento",
        "monto_descuento",
        "filtro_cantidad",
        "cantidad",
        "cantidad_restante",
        "filtro_periodo",
        "fecha_inicio",
        "fecha_fin",
        "filtro_categoria",
        "categorias",
        "filtro_carro_compra",
        "monto_carro_compra",
        "estado",
        "combinar_codigo",
        "cliente_id"
    ];
   

}
