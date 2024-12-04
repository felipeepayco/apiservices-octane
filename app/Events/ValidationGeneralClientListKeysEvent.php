<?php


namespace App\Events;


class ValidationGeneralClientListKeysEvent extends  Event
{
    public $arr_parametros;

    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}