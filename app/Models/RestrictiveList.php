<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PreRegister
 * @package App\Models
 * @property $category
 * @property $cel_number
 * @property $country
 * @property $prefijo
 * @property $created_at
 * @property $doc_number
 * @property $doc_type
 * @property $email
 * @property $names
 * @property $subcategory
 * @property $surnames
 * @property $user_type
 * @property $token
 * @property $restricted_user
 * @property $email_verified
 * @property $url_validate
 * @property $digito
 * @property $cliente_id
 * @property $utm_source
 * @property $utm_medium
 * @property $utm_campaign
 * @property $utm_content
 * @property $utm_term
 * @property $meta_tag
 * @property $password_jwt
 * @property $alianza_id
 * @property $social_network
 * @property $id_social_network
 * @property $plan_id
 * @property $proforma
 * @property $nombre_empresa
 * @property $request
 * @property $id_cliente_entidad_aliada
 */
class RestrictiveList extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'restrictive_list';

    /**
     * @var array
     */
    protected $fillable = [
        'category',
        'cel_number',
        'country',
        'prefijo',
        'created_at',
        'doc_number',
        'doc_type',
        'email',
        'names',
        'subcategory',
        'surnames',
        'user_type',
        'token',
        'restricted_user',
        'email_verified',
        'url_validate',
        'digito',
        'nombre_empresa',
        'cliente_id',
        'id_aliado',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'meta_tag',
        'password_jwt',
        'alianza_id',
        'social_network',
        'id_social_network',
        'plan_id',
        'proforma',
        'request',
        'id_cliente_entidad_aliada',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
