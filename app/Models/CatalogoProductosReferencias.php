<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductosReferencias extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos_referencias';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'nombre',
        'valor',
        'cantidad',
        'disponible',
        'estado',
        'id_catalogo_productos',
        'fecha_creacion',
        'rutaqr',
        'numerofactura',
        'descripcion',
        'moneda',
        'iva',
        'base_iva',
        'precio_descuento',
        'cobrounico',
        'fecha_expiracion',
        'url_respuesta',
        'url_confirmacion',
        'tipocobro',
        'fecha',
        'txtcodigo'
        ];

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
