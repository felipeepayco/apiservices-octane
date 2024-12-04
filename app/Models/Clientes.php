<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $actividad
 * @property int $aliado
 * @property string $apellido
 * @property string $celular
 * @property int $ciiu
 * @property string $codigo_qr
 * @property string $codigounico
 * @property int $comision_aliado
 * @property string $contrasena
 * @property int $detalle_estado
 * @property string $direccion
 * @property string $documento
 * @property string $email
 * @property string $estado_pin
 * @property int $fase_integracion
 * @property string $fb_user_id
 * @property string $fb_user_status
 * @property \Carbon\Carbon $fecha_creacion
 * @property \Carbon\Carbon $fecha_expedicion
 * @property \Carbon\Carbon $fecha_nacimiento
 * @property int $Id
 * @property int $id_aliado
 * @property int $id_categoria
 * @property int $id_ciudad
 * @property int $id_estado
 * @property string $id_pais
 * @property int $id_plan
 * @property int $id_regimen
 * @property int $id_region
 * @property int $id_reseller
 * @property int $id_subcategoria
 * @property int $ind_pais
 * @property string $key_cli
 * @property string $lat
 * @property string $lng
 * @property string $logo
 * @property string $nombre
 * @property string $nombre_empresa
 * @property string $observaciones
 * @property string $pagweb
 * @property int $perfil_id
 * @property string $pin
 * @property string $razon_social
 * @property int $rol
 * @property mixed $saldo_cliente
 * @property string $servicio
 * @property string $sexo
 * @property string $telefono
 * @property string $dir_tipo_nomenclatura
 * @property string $dir_tipo_propiedad
 * @property string $dir_detalle_tipo_propiedad
 * @property string $dir_numero_nomenclatura
 * @property string $dir_numero_puerta1
 * @property string $dir_numero_puerta2
 * @property string $dir_descripcion
 * @property string $tipo_cliente
 * @property int $tipo_doc
 * @property int $tipo_usuario
 * @property int $metatag_registro_id
 * @property int $ind_ciudad
 * @property int $digito
 * @property double $promedio_ventas
 * @property boolean $restricted_user
 * @property boolean $socios
 * @property int $tipo_nacionalidad_clientes
 * @property int $responsable_iva
 * @property-read Cifinlog[] $cifinlogs
 * @property-read Cifinrespuesta[] $cifinrespuestas
 * @property-read ClientesRedessociales[] $clientesRedessociales
 * @property-read CobrosFacturasLote[] $cobrosFacturasLotes
 * @property-read ComisionClienteAliado[] $comisionClienteAliados
 * @property-read CrmNegocio[] $crmNegocios
 * @property-read InspectorCelular[] $inspectorCelulars
 * @property-read InspectorCuentabancaria[] $inspectorCuentabancarias
 * @property-read InspectorEmail[] $inspectorEmails
 * @property-read InspectorRegistro[] $inspectorRegistros
 * @property-read InspectorTarjetas[] $inspectorTarjetas
 * @property-read InspectorTransacciones[] $inspectorTransacciones
 * @property-read MediosPagoTarifafijaClientes[] $mediosPagoTarifafijaClientes
 * @property-read PseTarifaCliente[] $pseTarifaClientes
 * @property-read SaldoAliados[] $saldoAliados
 * @property-read TipointegracionCliente[] $tipointegracionClientes
 * @property-read WsFiltrosLista[] $wsFiltrosListas
 * @property-read WsLista[] $wsListas
 */
class Clientes extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['actividad', 'aliado', 'apellido', 'celular', 'ciiu',
        'codigo_qr', 'codigounico', 'comision_aliado', 'contrasena',
        'detalle_estado', 'direccion', 'documento', 'email', 'estado_pin',
        'fase_integracion', 'fb_user_id', 'fb_user_status', 'fecha_creacion',
        'fecha_expedicion', 'fecha_nacimiento', 'Id', 'id_aliado', 'id_categoria',
        'id_ciudad', 'id_estado', 'id_pais', 'id_plan', 'id_regimen', 'id_region',
        'id_reseller', 'id_subcategoria', 'ind_pais', 'key_cli', 'lat', 'lng',
        'logo', 'nombre', 'nombre_empresa', 'observaciones', 'pagweb',
        'perfil_id', 'pin', 'razon_social', 'rol', 'saldo_cliente', 'servicio',
        'sexo', 'telefono', 'tipo_cliente', 'tipo_doc', 'tipo_usuario', 'metatag_registro_id',
        'dir_tipo_nomenclatura',
        'dir_numero_nomenclatura',
        'dir_numero_puerta1',
        'dir_numero_puerta2',
        'promedio_ventas',
        'dir_detalle_tipo_propiedad',
        'dir_tipo_propiedad',
        'dir_descripcion',
        'ind_ciudad',
        'digito',
        'restricted_user',
        'tipo_nacionalidad_clientes',
        'socios',
        'responsable_iva',
    ];

    /**
     * @var array
     */
    protected $dates = ['fecha_creacion', 'fecha_expedicion', 'fecha_nacimiento'];

    /**
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cifinlogs()
    {
        return $this->hasMany('Cifinlog', 'clienteid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cifinrespuestas()
    {
        return $this->hasMany('Cifinrespuesta', 'clienteid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientesRedessociales()
    {
        return $this->hasMany('ClientesRedessociales', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cobrosFacturasLotes()
    {
        return $this->hasMany('CobrosFacturasLote', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comisionClienteAliados()
    {
        return $this->hasMany('ComisionClienteAliado', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crmNegocios()
    {
        return $this->hasMany('CrmNegocio', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorCelulars()
    {
        return $this->hasMany('InspectorCelular', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorCuentabancarias()
    {
        return $this->hasMany('InspectorCuentabancaria', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorEmails()
    {
        return $this->hasMany('InspectorEmail', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorRegistros()
    {
        return $this->hasMany('InspectorRegistro', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorTarjetas()
    {
        return $this->hasMany('InspectorTarjetas', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inspectorTransacciones()
    {
        return $this->hasMany('InspectorTransacciones', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mediosPagoTarifafijaClientes()
    {
        return $this->hasMany('MediosPagoTarifafijaClientes', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pseTarifaClientes()
    {
        return $this->hasMany('PseTarifaCliente', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function saldoAliados()
    {
        return $this->hasMany('SaldoAliados', 'aliado_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tipointegracionClientes()
    {
        return $this->hasMany('TipointegracionCliente', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsFiltrosListas()
    {
        return $this->hasMany('WsFiltrosLista', 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wsListas()
    {
        return $this->hasMany(WsLista::class, 'id_cliente');
    }
}
