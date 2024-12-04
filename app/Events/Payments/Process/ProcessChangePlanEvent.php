<?php

namespace App\Events\Payments\Process;

use App\Events\Event;

/**
 * Description of Charge
 *
 * @author Leonardo
 */
class ProcessChangePlanEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}