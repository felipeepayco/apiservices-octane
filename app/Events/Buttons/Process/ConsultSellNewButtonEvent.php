<?php

namespace App\Events\Buttons\Process;
use App\Events\Event;
/**
 * Description of ConsultSellNewButtonEvent
 *
 * @author CDuque227
 */
class ConsultSellNewButtonEvent extends Event
{
    public $arr_parametros;
    public $request;

    public function __construct($arr_parametros, $request)
    {
        $this->arr_parametros = $arr_parametros;
        $this->request = $request;
    }
}