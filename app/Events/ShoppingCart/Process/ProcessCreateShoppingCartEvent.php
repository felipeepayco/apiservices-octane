<?php
namespace App\Events\ShoppingCart\Process;

use App\Events\Event;
/**
 * Description of ProcessCreateShoppingCartEvent
 *
 * @author Gustavo 
 */
class ProcessCreateShoppingCartEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}