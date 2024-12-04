<?php

namespace App\Events;
/**
 * Description of ConsultAccountBankCreateEvent
 *
 * @author Felipe
 */
class ConsultAccountBankCreateEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}