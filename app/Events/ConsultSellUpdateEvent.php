<?php
namespace App\Events;
/**
 * Description of ConsultSellUpdateEvent
 *
 * @author Efrain
 */
class ConsultSellUpdateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}