<?php
namespace App\Models\V2;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Relations\BelongsTo;
class Product extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'products';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'cliente_id',
        'fecha',
        'fecha_actualizacion',
        'txtcodigo',
        'ruta_qr',
        'route_link',
        'titulo',
        'numerofactura',
        'descripcion',
        'valor',
        'moneda',
        'iva',
        'base_iva',
        'precio_descuento',
        'cobrounico',
        'cantidad',
        'disponible',
        'estado',
        'fecha_expiracion',
        'url_respuesta',
        'url_confirmacion',
        'tipocobro',
        'catalogo_id',
        'nombre_contacto',
        'numero_contacto',
        'ventas',
        'img',
        'envio',
        'categorias',
        'referencias',
        'entidad_aliada',
        'fecha_creacion_cliente',
        'edata_estado',
        'configuraciones_referencias',
        'porcentaje_descuento',
        'mostrar_inventario',
        'origen',
        'destacado',
        'activo',
        'iva_activo',
        'ipoconsumo_activo',
        'ipoconsumo',
        'monto_neto',
        'epayco_logistica',
        'lista_proveedores',
        'peso_real',
        'alto',
        'largo',
        'ancho',
        'valor_declarado'
    ];



    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(Catalogue::class, "catalogo_id", "_id");
    }


}
