<?php
namespace App\Events;
/**
 * Description of ValidationGeneralSellUpdateEvent
 *
 * @author Efrain
 */
class ValidationGeneralSellUpdateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}