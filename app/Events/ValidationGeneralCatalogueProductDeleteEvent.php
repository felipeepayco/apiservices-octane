<?php
namespace App\Events;
/**
 * Description of ValidationGeneralCatalogurProductListEvent
 *
 * @author daniel
 */
class ValidationGeneralCatalogueProductDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}