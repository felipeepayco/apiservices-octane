<?php
namespace App\Events;

/**
 * Description of ValidationGeneralCatalogueProductListElasticEvent
 *
 * @author Efrain, Gilberto
 */
class ValidationGeneralCatalogueProductListElasticEvent extends Event{
    public $arr_parametros;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
