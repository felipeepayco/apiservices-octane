<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueCategoriesUpdateEvent
 *
 * @author Efrain
 */
class ConsultCatalogueCategoriesUpdateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}