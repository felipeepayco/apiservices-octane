<?php
namespace App\Events\Buttons\Validation;

use App\Events\Event;
/**
 * Description of ValidationGeneralButtonDeleteEvent
 *
 * @author CDuque227
 */
class ValidationGeneralButtonDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}