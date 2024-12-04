<?php
namespace App\Events;
/**
 * Description of ConsultCatalogueProductReferenceCreateEvent
 *
 * @author Efrain
 */
class ConsultCatalogueProductReferenceCreateEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}