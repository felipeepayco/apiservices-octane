<?php
namespace App\Events;
/**
 * Description of ValidationGeneralSellListEvent
 *
 * @author Efrain
 */
class ValidationGeneralCatalogueProductNewEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}