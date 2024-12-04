<?php
namespace App\Events\Buttons\Process;
use App\Events\Event;
/**
 * Description of ConsultButtonListEvent
 *
 * @author CDuque227
 */
class ConsultButtonListEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}