<?php

namespace App\Events\QueuesEpayco;

use App\Events\Event;

/**
 * Description of QueuesEpaycoEvent
 *
 * @author Felipe
 */
class QueuesEpaycoEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}