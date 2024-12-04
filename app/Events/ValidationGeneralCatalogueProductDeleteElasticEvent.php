<?php
namespace App\Events;
/**
 * Description of ValidationGeneralCatalogueProductDeleteElasticEvent
 *
 * @author daniel, Gilberto
 */
class ValidationGeneralCatalogueProductDeleteElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}