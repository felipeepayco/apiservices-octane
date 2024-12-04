<?php

namespace App\Events\Payments\Validation;
use App\Events\Event;

/**
 * Description of ValidationTransactionDPEvent
 *
 * @author Gerson
 */
class ValidationTransactionDPEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}