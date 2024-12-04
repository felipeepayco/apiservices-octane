<?php
namespace App\Events\ShoppingCart\Process;

use App\Events\Event;
/**
 * Description of GetShippingInfoEvent
 *
 * @author Leonardo Melendez 
 */
class GetShippingInfoEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
