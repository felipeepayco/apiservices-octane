<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class BblNote extends Model
{

    protected $table = 'bbl_notas';
    protected $fillable = [
        'nota',
        'bbl_comprador_id',
        'bbl_cliente_id',
    ];

}
