<?php
namespace App\Events\ShoppingCart\Process;

use App\Events\Event;
/**
 * Description of ProcessPaymentReceiptEvent
 *
 * @author Andrés Duque 
 */
class ProcessPaymentReceiptEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
