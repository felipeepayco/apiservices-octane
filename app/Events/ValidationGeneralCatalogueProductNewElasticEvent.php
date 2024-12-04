<?php
namespace App\Events;
/**
 * Description of ValidationGeneralCatalogueProductNewElasticEvent
 * para la V2 con elastic
 *
 * @author Efrain, Gilberto
 */
class ValidationGeneralCatalogueProductNewElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}