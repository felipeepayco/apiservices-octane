<?php

namespace App\Events\Payments\Process;

use App\Events\Event;

/**
 * Description of ProcessTokenCardEvent
 *
 * @author Felipe
 */
class ProcessTokenCardV2Event extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}