<?php
namespace App\Events;
/**
 * Description of ConsultSellDeleteEvent
 *
 * @author Efrain
 */
class ConsultSellDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}