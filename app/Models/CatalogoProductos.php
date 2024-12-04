<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CatalogoProductos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalogo_productos';

    /**
     * @var array
     */
    protected $fillable = ['id', 'cliente_id', 'fecha', 'txtcodigo','rutaqr','titulo','numerofactura','descripcion','moneda','valor','iva','base_iva','precio_descuento','cobrounico','cantidad','disponible','estado','fecha_expiracion','ur_respuesta','url_confirmacion','tipocobro','catalogo_id',"nombre_contacto","numero_contacto"];

    /**
     * @var array
     */
    protected $dates = ['fecha','fecha_expiracion'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
