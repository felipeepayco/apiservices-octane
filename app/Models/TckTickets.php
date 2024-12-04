<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TckTickets
 * @package App\Models
 */
class TckTickets extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_tickets';

    /**
     * @var array
     */
    protected $fillable = [
        'dominio',
        'asunto',
        'emailusario',
        'emailusuario2',
        'emailagente',
        'pregunta',
        'producto_id',
        'incidencias_id',
        'prioridad_id',
        'baseconocimientos_id',
        'usuarios_id',
        'masterestado_id',
        'departamentos_id',
        'mensaje_id',
        'nombre_cliente',
        'clienteid',
        'etapa_id',
        'departamento_inicial'
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
    public $timestamps = true;

    public function DepartamentoInicial()
    {
        return $this->belongsTo(TckDepartamentos::class, 'departamento_inicial');
    }

    public function Departamento()
    {
        return $this->belongsTo(TckDepartamentos::class, 'departamentos_id');
    }

    public function BaseConocimiento()
    {
        return $this->belongsTo(TckBaseConocimientos::class, 'baseconocimientos_id');
    }

    public function Incidencia()
    {
        return $this->belongsTo(TckIncidencias::class, 'incidencias_id');
    }

    public function MasterEstado()
    {
        return $this->belongsTo(TckMasterEstado::class, 'masterestado_id');
    }

    public function Prioridad()
    {
        return $this->belongsTo(TckPrioridad::class, 'prioridad_id');
    }
}