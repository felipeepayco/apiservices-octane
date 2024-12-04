<?php
namespace App\Events;
/**
 * Description of ConsultSellEditEvent
 *
 * @author Efrain
 */
class ConsultSellEditEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}