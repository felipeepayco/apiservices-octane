<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueProductListEvent
 *
 * @author Efrain
 */

class ConsultCatalogueProductListEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}