<?php

namespace App\Events\PaycoLink\Validation;

use App\Events\Event;

/**
 * Description of ValidationPaycoLinkPaymentCreateEvent
 *
 */
class ValidationPaycoLinkPaymentCreateEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}