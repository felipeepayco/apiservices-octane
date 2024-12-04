<?php
namespace App\Events\Catalogue\Process;

use App\Events\Event;
/**
 * Description of ConsultSellDeleteEvent
 *
 * @author Felipe
 */
class CatalogueDeleteEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}