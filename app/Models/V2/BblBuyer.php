<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class BblBuyer extends Model
{

    protected $table = 'bbl_comprador';
    protected $fillable = [
        'correo',
        'nombre',
        'apellido',
        'documento',
        'telefono',
        'ind_pais_tlf',
        'pais',
        'departamento',
        'ciudad',
        'direccion',
        'otros_detalles',
        'bbl_cliente_id',
        'monto_total_consumido',
        'ultima_compra',
        "codigo_pais",
        "codigo_dane"
    ];

}
