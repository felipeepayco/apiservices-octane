<?php
namespace App\Events\Catalogue\Validation;
use App\Events\Event;
/**
 * Description of ValidationGeneralSellDeleteEvent
 *
 * @author Felipe
 */
class ValidationGeneralCatalogueInactiveEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
