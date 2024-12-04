<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkPagoPse
 * @package App\Models
 * @property int $id
 * @property int $id_cliente
 * @property string $url_pago
 * @property string $url_respuesta
 * @property string $url_confirmacion
 * @property string $metodo_confirmacion
 * @property float $valor_pago
 * @property int $ref_payco
 * @property string $id_factura
 * @property string $producto
 * @property object $fecha
 * @property string $email_pago
 * @property string $tipo_documento
 * @property string $documento
 * @property string $nombre
 * @property string $apellido
 * @property string $celular
 * @property string $telefono
 * @property bool   $testMode
 */
class LinkPagoPse extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'link_pago_pse';

    /**
     * @var array
     */
    protected $fillable = [
        'url_pago',
        'url_respuesta',
        'url_confirmacion',
        'metodo_confirmacion',
        'valor_pago',
        'ref_payco',
        'id_factura',
        'producto',
        'fecha',
        'id_cliente',
        'email_pago',
        'tipo_documento',
        'documento',
        'nombre',
        'apellido',
        'celular',
        'telefono',
        'test'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

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
