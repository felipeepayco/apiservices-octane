<?php
namespace App\Events;
/**
 * Description of ValidationGeneralSellDeleteEvent
 *
 * @author Felipe
 */
class ValidationGeneralCatalogueDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}