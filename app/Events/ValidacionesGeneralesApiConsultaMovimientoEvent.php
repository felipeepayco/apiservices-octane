<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Events;

/**
 * Description of ValidacionesGeneralesApiConsultaMovimientoEvent
 *
 * @author Efrain
 */
class ValidacionesGeneralesApiConsultaMovimientoEvent extends Event{
    public $arr_parametros;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
