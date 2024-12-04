<?php


namespace App\Events\Subscriptions\Process;

use App\Events\Event;

class ActiveDomiciliationsEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}