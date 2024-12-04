<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueTopSellingProductsListElasticEvent
 *
 * @author Gustavo,Gilberto
 */

class ConsultCatalogueTopSellingProductsListElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}