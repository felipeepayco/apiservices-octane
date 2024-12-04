<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class TckRespuestaTicket
 * @package App\Models
 */
class TckRespuestaTicket extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tck_respuestaticket';

    /**
     * @var array
     */
    protected $fillable = [
        'created_at',
        'tipo_user',
        'creado_por',
        'texto',
        'firma',
        'tiporespuesta',
        'estadosrespuestas_id',
        'tickets_id'
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

    public function EstadoRespuesta()
    {
        return $this->belongsTo(TckEstadosRespuestas::class, 'estadosrespuestas_id');
    }

    public function Ticket()
    {
        return $this->belongsTo(TckTicket::class, 'tickets_id');
    }
}