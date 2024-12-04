<?php
namespace App\Events;
/**
 * Description of ConsultSellDeleteEvent
 *
 * @author Felipe
 */
class CatalogueProductDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}