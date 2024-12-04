<?php
namespace App\Events\Catalogue\Validation\DiscountCode;
use App\Events\Event;

/**
 * Description of ValidationCatalogueDiscountCodeEvent
 *
 * @author Efrain
 */
class ValidationCatalogueDiscountCodeEvent extends Event{
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