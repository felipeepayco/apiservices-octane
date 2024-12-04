<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueProductListEvent
 *
 * @author Efrain,Gilberto
 */

class ConsultCatalogueProductListElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}