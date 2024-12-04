<?php
namespace App\Events\ShoppingCart\Validation;

use App\Events\Event;
/**
 * Description of ValidationEmptyCartEvent
 *
 * @author Gilberto
 */
class ValidationEmptyCartEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}