<?php

namespace App\Events\Vende\Process;
use App\Events\Event;
/**
 * Description of ProcessShowConfigurationCatalogueEvent
 *
 * @author leomar
 */
class ProcessShowConfigurationCatalogueEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}