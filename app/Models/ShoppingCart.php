<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ShoppingCart extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shoppingcart';

    /**
     * @var array
     */
    protected $fillable = ['id', 'clienteId', 'estado', 'cantidad', 'fecha', 'total', 'productos', "ip", "catalogo_id"];

    /**
     * @var array
     */
    protected $dates = ['fecha'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
