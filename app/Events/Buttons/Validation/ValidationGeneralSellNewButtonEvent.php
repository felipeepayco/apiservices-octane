<?php
namespace App\Events\Buttons\Validation;

use App\Events\Event;
/**
 * Description of ValidationGeneralSellNewButtonEvent
 *
 * @author CDuque227
 */
class ValidationGeneralSellNewButtonEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}