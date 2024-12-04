<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class BblPurchase extends Model
{

    protected $table = 'bbl_compras';
    protected $fillable = [
        'carrito_id',
        'monto',
        'fecha',
        'estado',
        'bbl_comprador_id',
        'referencia_epayco',
        'cantidad_productos'
    ];

}
