<?php
namespace App\Events\Customer\Process;
use App\Events\Event;
/**
 * Description of ValidationGeneralSellListEvent
 *
 * @author Felipe
 */
class CustomerNewEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}