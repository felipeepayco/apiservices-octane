<?php
namespace App\Events;
/**
 * Description of ConsultProfileEditEvent
 *
 * @author Efrain
 */
class ConsultProfileEditEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}