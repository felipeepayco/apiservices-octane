<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_cliente
 * @property string $documento
 * @property string $nombre
 * @property string $apellido
 * @property string telefono
 * @property string $ext
 * @property string $celular
 * @property string $tipo_contacto
 * @property string $profesion
 * @property int $tipo_doc
 * @property int $ind_pais
 * @property string $email
 * @property bool $socios
 * @property \Carbon\Carbon $fecha_exp
 */
class ContactosClientes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contactos_clientes';

    /**
     * @var array
     */
    protected $fillable = ['id', 'id_cliente', 'documento', 'nombre','apellido', 'telefono', 'ext', 'celular', 'tipo_contacto','email', 'tipo_doc', 'ind_pais','profesion','socios'];
    /**
     * @var array
     */
    protected $dates = ['fecha_exp'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
