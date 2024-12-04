<?php
namespace App\Events\ShoppingCart\Validation;

use App\Events\Event;
/**
 * Description of ValidationPaymentReceiptEvent
 *
 * @author Andrés Duque 
 */
class ValidationPaymentReceiptEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
