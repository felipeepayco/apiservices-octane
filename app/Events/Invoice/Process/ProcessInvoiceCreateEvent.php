<?php

namespace App\Events\Invoice\Process;

use App\Events\Event;

class ProcessInvoiceCreateEvent extends Event
{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
