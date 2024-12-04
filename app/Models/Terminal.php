<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PreRegister
 * @package App\Models
 * @property $id
 * @property $codigounico
 * @property $terminalcode
 * @property $enpruebas
 * @property $estado
 * @property $fecha_eliminacion
 */

class Terminal extends Model
{
    protected $table = 'terminal';

    protected $fillable = [
        'unique_code',
        'terminal_code',
        'client_id',
        'in_tests',
        'status',
        'deleted_at',
    ];

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $hidden = [];
}