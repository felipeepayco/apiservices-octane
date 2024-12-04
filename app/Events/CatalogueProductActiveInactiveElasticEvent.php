<?php
namespace App\Events;
/**
 * Description of CatalogueActiveInactiveElasticEvent
 *
 * @author Felipe, Gilberto
 */
class CatalogueProductActiveInactiveElasticEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}