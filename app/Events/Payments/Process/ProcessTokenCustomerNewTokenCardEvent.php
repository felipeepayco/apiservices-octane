<?php

namespace App\Events\Payments\Process;

use App\Events\Event;

/**
 * Description of ProcessTokenCustomerNewTokenCardEvent
 *
 * @author Felipe
 */
class ProcessTokenCustomerNewTokenCardEvent extends Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}