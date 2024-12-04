<?php
namespace App\Events\Catalogue\Process\Category;
use App\Events\Event;

/**
 * Description of ConsultCatalogueCategoriesNewEvent
 *
 * @author Efrain
 */
class ConsultCatalogueCategoriesNewEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}