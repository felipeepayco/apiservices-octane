<?php

namespace App\Events\PaycoLink\Process;

use App\Events\Event;

/**
 * Description of ProcessPaycoLinkPaymentCreateEvent
 *
 */
class ProcessPaycoLinkPaymentCreateEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}