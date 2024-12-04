<?php
namespace App\Events\Subscription\Process;
use App\Events\Event;
/**
 *
 * @author Leomar
 */
class ProcessTokenCardDefaultEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}