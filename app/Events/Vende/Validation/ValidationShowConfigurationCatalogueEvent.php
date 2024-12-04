<?php

namespace App\Events\Vende\Validation;
use App\Events\Event;
/**
 * Description of ValidationShowConfigurationCatalogueEvent
 *
 * @author leomar
 */
class ValidationShowConfigurationCatalogueEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}