<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InspectoRegistro
 * @package App\Models
 */
class InspectorRegistro extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inspector_registro';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'fecha',
        'lastupdate',
        'validatemail',
        'validcelular',
        'logcifincedula',
        'lastconfronta',
        'lastevaluacionconfronta',
        'estadoconfronta',
        'cliente_id'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
