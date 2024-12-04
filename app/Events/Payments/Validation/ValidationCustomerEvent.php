<?php

namespace App\Events\Payments\Validation;

use App\Events\Event;

/**
 * Description of ValidationCustomerEvent
 *
 * @author Felipe
 */
class ValidationCustomerEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}