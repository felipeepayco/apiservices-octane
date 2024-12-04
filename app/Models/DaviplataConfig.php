<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $idCliente
 * @property int $ip
 * @property string $descripcion
 */
class DaviplataConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daviplata_config';

    /**
     * @var array
     */
    protected $fillable = ['id',
        'client_id_pruebas',
        'client_id_produccion',
        'client_secret_pruebas',
        'client_secret_produccion',
        'url_login_pruebas',
        'url_login_produccion',
        'url_compra_pruebas',
        'url_compra_produccion',
        'url_confirmacion_pruebas',
        'url_confirmacion_produccion',
        'cert',
        'key',
        'id_comercio',
        'terminal1',
        'terminal2',
        'cert_prod',
        'key_prod',
        'client_id_davivienda_catalogo_prod',
        'client_secret_davivienda_catalogo_prod',
        'base_url_dev',
        'base_url_prod',
        'id_comercio_mpd',

    ];

    /**
     * @var array
     */
    protected $protected = ['id'];


}
