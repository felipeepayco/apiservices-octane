<?php

namespace App\Events\Country\Process;

use App\Events\Event;

/**
 * Description of ProcessGetConfCountriesEvent
 *
 * @author José Artigas
 */
class ProcessGetConfCountriesEvent extends Event
{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
