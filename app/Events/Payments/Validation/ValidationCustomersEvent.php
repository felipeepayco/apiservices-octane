<?php

namespace App\Events\Payments\Validation;

use App\Events\Event;

/**
 * Description of ValidationCustomersEvent
 *
 * @author Felipe
 */
class ValidationCustomersEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}