<?php
namespace App\Events;
/**
 * Description of ApiConsultaMovimientoEvent
 *
 * @author Efrain
 */
class ApiConsultaMovimientoEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}