<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BblPlan extends Model
{
    protected $table = 'bbl_planes';
    protected $fillable=[
        "tiendas",
        "productos",
        "categorias",
        "analitica",
        "nombre",
        "fecha_creacion",
        "precio"
    ];

}
