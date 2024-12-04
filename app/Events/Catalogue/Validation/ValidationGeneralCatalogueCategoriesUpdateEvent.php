<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Events\Catalogue\Validation;
use App\Events\Event;

/**
 * Description of ValidationGeneralCatalogueCategoriesUpdateEvent
 *
 * @author Efrain
 */
class ValidationGeneralCatalogueCategoriesUpdateEvent extends Event{
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