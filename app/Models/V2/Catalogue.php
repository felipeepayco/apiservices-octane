<?php

namespace App\Models\V2;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Relations\EmbedsMany;

class Catalogue extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'catalogs';
    protected $primaryKey = 'id';
    protected $dates = ['fecha_creacion_certificado'];
    protected $fillable = ['id',
        'estado',
        'color',
        'correo_contacto',
        'recogida_automatica',
        'nombre',
        'entidad_aliada',
        'fecha_creacion_cliente',
        'dominio_propio',
        'nombre_empresa',
        'proveedor_envios',
        'configuracion_recogida_id',
        'apellido_remitente',
        'moneda', 'idioma',
        'tipo_documento_remitente',
        'procede',
        'direccion_recogida',
        'eliminado_valor_dominio_propio',
        'analiticas', 'tipo_remitente',
        'envio_gratis',
        'valor_subdominio_propio',
        'categorias',
        'razon_social_remitente',
        'ciudad_recogida',
        'imagen',
        'telefono_remitente',
        'fecha_actualizacion',
        'epayco_logistica',
        'banners',
        'lista_proveedores',
        'eliminado_valor_subdominio_propio',
        'departamento_recogida',
        'fecha',
        'indicativo_pais',
        'documento_remitente',
        'telefono_contacto',
        'nombre_remitente',
        'cliente_id',
        'valor_dominio_propio',
        'whatsapp_activo',
        'progreso',
        'edata_estado',
        'activo',
        'edata_estado_anterior',
        'clientId',
        'success',
        "posee_certificado",
        "fecha_creacion_certificado",
    ];

    public function categories(): EmbedsMany
    {
        return $this->embedsMany(Category::class, 'categorias');
    }

}
