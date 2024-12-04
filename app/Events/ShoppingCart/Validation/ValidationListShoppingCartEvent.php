<?php
namespace App\Events\ShoppingCart\Validation;

use App\Events\Event;
/**
 * Description of ProcessCreateShoppingCartEvent
 *
 * @author Gustavo 
 */
class ValidationListShoppingCartEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
