<?php

namespace App\Events\Invoice\Validation;

use App\Events\Event;

class ValidationInvoiceCreateEvent extends Event
{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
