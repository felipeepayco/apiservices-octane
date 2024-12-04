<?php
namespace App\Events;
/**
 * Description of CatalogueProductDeleteElasticEvent
 *
 * @author Felipe, Gilberto
 */
class CatalogueProductDeleteElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}