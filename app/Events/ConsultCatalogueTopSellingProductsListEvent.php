<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueTopSellingProductsListEvent
 *
 * @author Gustavo
 */

class ConsultCatalogueTopSellingProductsListEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}