<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueCategoriesListEvent
 *
 * @author Efrain
 */
class ConsultCatalogueCategoriesListEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}