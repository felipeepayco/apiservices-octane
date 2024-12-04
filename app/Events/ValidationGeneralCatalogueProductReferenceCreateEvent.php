<?php
namespace App\Events;
/**
 * Description of ValidationGeneralCatalogueProductReferenceCreateEvent
 *
 * @author Efrain
 */
class ValidationGeneralCatalogueProductReferenceCreateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}