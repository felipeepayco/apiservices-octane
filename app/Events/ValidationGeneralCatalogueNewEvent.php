<?php
namespace App\Events;
/**
 * Description of ValidationGeneralSellListEvent
 *
 * @author Felipe
 */
class ValidationGeneralCatalogueNewEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}