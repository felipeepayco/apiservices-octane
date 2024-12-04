<?php

namespace App\Events;
/**
 * Description of ePaycoWithdrawEvent
 *
 * @author Felipe
 */
class ePaycoWithdrawEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}