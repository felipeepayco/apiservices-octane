<?php
namespace App\Events;
/**
 * Description of ValidationGeneralCatalogueProductActiveInactiveElasticEvent
 *
 * @author leonardo, melendez
 */
class ValidationGeneralCatalogueProductActiveInactiveElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}