<?php
namespace App\Events;
/**
 * Description of ValidationGeneralSellDeleteEvent
 *
 * @author Efrain
 */
class ValidationGeneralSellDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}