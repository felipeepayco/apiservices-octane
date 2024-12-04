<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property \Carbon\Carbon $fecha_creacion
 * @property string $nombre
 * @property string $descripcion
 * @property int $numero_trx
 * @property int $porcentaje
 * @property int $valor_trx
 * @property double $precio
 * @property float $precio_mensual
 * @property float $precio_semestral
 * @property float $precio_anual
 * @property int $tipo_plan
 * @property float $monto_cubierto
 * @property int $periodicidad
 * @property int $costo_retiro
 * @property int $costo_por_mil
 * @property int $cuatro_por_mil
 * @property int $retefuente
 * @property int $reteiva
 * @property int $reteica
 * @property int $saldo_promedio_pactado
 * @property int $transacciones_promedio_pactado
 * @property int $producto_default
 * @property int $cambiar_plan_automaticamente
 * @property int $informar_cambio_plan
 * @property int $calcular_comision_transaccion
 * @property int $agregador
 * @property int $project_limit
 * @property int $record_limit
 * @property string $tipo_medios_pago_id
 * @property string $configuracion
 * @property int $id_producto_siigo
 * @property int $id_grupo_inventario_siigo
 * @property int $recomendado
 * @property int $id_impuesto_retencion
 * @property string $codigo_sigo
 * @property string $codigo_sigo_2020
 * @property string $codigo_siigo
 * @property string $codigo_siigo_2020
 */
class Productos extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'fecha_creacion',
        'nombre',
        'descripcion',
        'numero_trx',
        'porcentaje',
        'valor_trx',
        'precio',
        'precio_mensual',
        'precio_semestral',
        'precio_anual',
        'tipo_plan',
        'monto_cubierto',
        'periodicidad',
        'costo_retiro',
        'costo_por_mil',
        'cuatro_por_mil',
        'retefuente',
        'reteiva',
        'reteica',
        'saldo_promedio_pactado',
        'transacciones_promedio_pactado',
        'producto_default',
        'cambiar_plan_automaticamente',
        'informar_cambio_plan',
        'calcular_comision_transaccion',
        'agregador',
        'project_limit',
        'record_limit',
        'tipo_medios_pago_id',
        'configuracion',
        'id_producto_siigo',
        'id_grupo_inventario_siigo',
        'recomendado',
        'id_impuesto_retencion',
        'codigo_siigo',
        'codigo_siigo_2020',
    ];
    /**
     * @var string
     */
    protected $primaryKey = 'id';
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

    /**
     * Get the products_clients for the product.
     */
    public function productosClientes()
    {
        return $this->hasMany(ProductosClientes::class, 'producto_id');
    }
}

