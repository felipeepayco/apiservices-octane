<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueCategoriesDeleteEvent
 *
 * @author Efrain
 */
class ConsultCatalogueCategoriesDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}