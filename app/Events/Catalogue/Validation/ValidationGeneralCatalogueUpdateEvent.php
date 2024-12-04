<?php
namespace App\Events\Catalogue\Validation;
use App\Events\Event;
/**
 * Description of ValidationGeneralSellListEvent
 *
 * @author Felipe
 */
class ValidationGeneralCatalogueUpdateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}